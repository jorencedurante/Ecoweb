<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\BottleCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BottleScanController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lrn' => 'required|string',
            'bottle_count' => 'required|integer|min:1',
        ]);

        $lrn = trim((string) $validated['lrn']);
        $bottleCount = (int) $validated['bottle_count'];

        Log::info('Bottle scan request received', [
            'lrn' => $lrn,
            'bottle_count' => $bottleCount,
        ]);

        try {
            $student = DB::table('students')
                ->whereRaw('TRIM(lrn) = ?', [$lrn])
                ->first();

            if (!$student) {
                Log::warning('Bottle scan: student not found', [
                    'lrn' => $lrn,
                    'bottle_count' => $bottleCount,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Student not found',
                    'debug' => [
                        'lrn_received' => $lrn,
                        'student_table' => 'students',
                        'lrn_column' => 'lrn',
                    ],
                ], 404);
            }

            Log::info('Bottle scan: student found', [
                'student_id' => $student->id,
                'lrn' => $lrn,
                'student_name' => trim(
                    ($student->first_name ?? '') . ' ' .
                    ($student->middle_name ?? '') . ' ' .
                    ($student->last_name ?? '')
                ),
            ]);

            $pointsEarned = $bottleCount;

            DB::transaction(function () use ($student, $lrn, $bottleCount, $pointsEarned) {
                $now = now('Asia/Manila');

                $record = BottleCollection::create([
                    'student_id' => $student->id,
                    'lrn' => $lrn,
                    'collection_date' => $now->toDateString(),
                    'collection_time' => $now->format('H:i:s'),
                    'bottle_count' => $bottleCount,
                    'points_earned' => $pointsEarned,
                    'created_by' => null,
                ]);

                Student::where('id', $student->id)->increment('total_points', $pointsEarned);

                Log::info('Bottle scan: record saved', [
                    'record_id' => $record->id,
                    'student_id' => $student->id,
                    'lrn' => $lrn,
                    'bottle_count' => $bottleCount,
                    'points' => $pointsEarned,
                ]);
            });

            $studentName = trim(implode(' ', array_filter([
                $student->first_name ?? '',
                $student->middle_name ?? '',
                $student->last_name ?? '',
            ])));

            return response()->json([
                'success' => true,
                'message' => 'Bottle collection saved successfully',
                'data' => [
                    'lrn' => $lrn,
                    'student' => $studentName ?: 'Unknown',
                    'bottle_count' => $bottleCount,
                    'points' => $pointsEarned,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Bottle scan failed for LRN ' . $lrn . ': ' . $e->getMessage(), [
                'lrn' => $lrn,
                'bottle_count' => $bottleCount,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error while saving bottle scan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
