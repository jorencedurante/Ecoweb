<div class="modal-overlay" id="permissionDeniedModal" role="dialog" aria-modal="true" aria-labelledby="permissionDeniedTitle" aria-describedby="permissionDeniedMessage">
    <div class="modal-content permission-denied-modal">
        <div class="permission-denied-icon" aria-hidden="true">
            <x-icon name="shield-alert" :size="30" />
        </div>
        <h2 id="permissionDeniedTitle">Permission Required</h2>
        <p id="permissionDeniedMessage">You don't have permission to add students. Please contact your Teacher or Principal to request access.</p>
        <button type="button" class="btn btn-primary" id="permissionDeniedOk" data-modal-close>OK, Got It</button>
    </div>
</div>

<style>
    #permissionDeniedModal .permission-denied-modal {
        max-width: 420px;
        width: min(100%, 420px);
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
        padding: 32px 28px;
        text-align: center;
    }

    #permissionDeniedModal .permission-denied-icon {
        width: 62px;
        height: 62px;
        margin: 0 auto 18px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #b45309;
        background: #fef3c7;
    }

    #permissionDeniedModal h2 {
        font-size: 19px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 10px;
    }

    #permissionDeniedModal p {
        font-size: 14px;
        line-height: 1.6;
        color: #6b7280;
        margin: 0 0 24px;
    }

    #permissionDeniedModal .btn {
        min-width: 150px;
    }

    @media (max-width: 480px) {
        #permissionDeniedModal .permission-denied-modal {
            margin: 16px;
            padding: 26px 20px;
        }
    }
</style>
