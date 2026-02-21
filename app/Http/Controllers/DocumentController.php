<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\ApplicantDocument;
use Illuminate\Http\Request;

class DocumentController extends Controller
{

    public function upload(Request $request, $id)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }

        $validated = $request->validate([
            'document_type' => 'required|string|max:50',
            'file' => 'required|file|max:5120',
        ]);

        $filePath = $request->file('file')->store('applicant_documents');

        $document = ApplicantDocument::create([
            'applicant_id' => $applicant->id,
            'document_type' => $validated['document_type'],
            'file_path' => $filePath,
        ]);

        return response()->json($document, 201);
    }


    // GET /api/applicants/{id}/documents
    public function index($id)
    {
        $applicant = Applicant::with('documents')->find($id);
        if (!$applicant) {
            return response()->json(['message' => 'Applicant not found'], 404);
        }
        return response()->json($applicant->documents);
    }



}