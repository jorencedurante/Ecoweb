// Lightweight DOM stub to verify the permission guard + modal behaviour
// without a browser/jsdom dependency. Run with:
//   node tests/js/modal-guard.test.mjs
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

class El {
    constructor(id = '') {
        this.id = id;
        this.dataset = {};
        this.attrs = {};
        this._classes = new Set();
        this.textContent = '';
        this.innerHTML = '';
    }
    get classList() {
        const set = this._classes;
        return {
            add: (c) => set.add(c),
            remove: (c) => set.delete(c),
            contains: (c) => set.has(c),
        };
    }
    setAttribute(k, v) { this.attrs[k] = v; }
    removeAttribute(k) { delete this.attrs[k]; }
    getAttribute(k) { return this.attrs[k] ?? null; }
    querySelector() { return this._message || null; }
    addEventListener() {}
}

const els = {
    addStudentModal: new El('addStudentModal'),
    permissionDeniedModal: new El('permissionDeniedModal'),
};
els.permissionDeniedModal._message = new El('permissionDeniedMessage');

globalThis.document = {
    getElementById: (id) => els[id] || null,
    querySelectorAll: () => [],
    addEventListener: () => {},
    body: { classList: { add() {}, remove() {} } },
};
globalThis.window = globalThis;
globalThis.event = null; // not used by the guard itself
globalThis.addEventListener = () => {};

const js = readFileSync(new URL('../../public/js/app.js', import.meta.url), 'utf8');
const start = js.indexOf('// ---------- Permission denied popup ----------');
const end = js.indexOf('// ---------- Modals ----------');
assert.ok(start >= 0 && end > start, 'expected helper block in app.js');
eval(js.slice(start, end));

const btn = new El('addStudentBtn');
btn.dataset.modalTarget = 'addStudentModal';
btn.dataset.permissionAction = 'add';
btn.dataset.permissionAllowed = 'false';

// Ensure a stale state cannot survive: set form modal open first.
els.addStudentModal.classList.add('show');
assert.equal(els.addStudentModal.classList.contains('show'), true, 'setup');

const allowed = window.EcoPermission.guard(btn);

assert.equal(allowed, false, 'denied action must be blocked');
assert.equal(els.addStudentModal.classList.contains('show'), false, 'form modal must be closed');
assert.equal(els.addStudentModal.getAttribute('aria-hidden'), 'true', 'form modal marked hidden');
assert.equal(els.permissionDeniedModal.classList.contains('show'), true, 'popup is shown');
assert.match(els.permissionDeniedModal._message.textContent, /contact your Teacher or Principal/);

// Granted permission opens only the form modal.
els.permissionDeniedModal.classList.remove('show');
btn.dataset.permissionAllowed = 'true';
assert.equal(window.EcoPermission.guard(btn), true, 'granted action allowed');
assert.equal(els.permissionDeniedModal.classList.contains('show'), false, 'popup stays closed');

console.log('modal-guard: 6 assertions passed');
