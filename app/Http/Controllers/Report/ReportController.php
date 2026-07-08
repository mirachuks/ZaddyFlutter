<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'job_id' => ['required', 'integer', 'exists:jobs,id'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $job = Job::find($request->job_id);

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found.',
            ], 404);
        }

        $report = Report::create([
            'user_id' => $request->user()->id,
            'job_id' => $job->id,
            'msg' => $request->message,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully.',
            'data' => $report,
        ], 201);
    }
}
