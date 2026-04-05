<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Applicant;
use App\Models\ApplicantDocument;
use App\Http\Requests\UploadApplicantDocumentRequest;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function upload(UploadApplicantDocumentRequest $request, int $id)
    {
        $applicant = Applicant::find($id);
        if (!$applicant) return response()->json(['message' => 'Applicant not found'], 404);

        if (!$this->canAccessApplicantDocuments($request, $applicant)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($applicant->status !== Applicant::STATUS_PENDING) {
            return response()->json(['message' => 'Only pending applications can upload files'], 409);
        }

        $validated = $request->validated();
        $uploaded = $request->file('file');


        $binaryData = file_get_contents($uploaded->getRealPath());

        $document = ApplicantDocument::create([
            'applicant_id'      => $applicant->id,
            'document_type'     => strtolower($validated['document_type']),
            'file_content'      => $binaryData,
            'original_filename' => $uploaded->getClientOriginalName(),
            'mime_type'         => $uploaded->getMimeType(),
            'file_size'         => $uploaded->getSize(),
            'sha256'            => hash_file('sha256', $uploaded->getRealPath()),
            'scan_status'       => 'pending',
        ]);

        return response()->json($document->makeHidden('file_content'), 201);
    }


    public function download(Request $request, int $id, int $documentId)
    {
        $document = ApplicantDocument::where('applicant_id', $id)->findOrFail($documentId);


        return response($document->file_content)
            ->header('Content-Type', $document->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $document->original_filename . '"');
    }

    public function index(Request $request, int $id)
    {
        $applicant = Applicant::with('documents')->find($id);
        if (!$applicant) return response()->json(['message' => 'Not found'], 404);

        if (!$this->canAccessApplicantDocuments($request, $applicant)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($applicant->documents);
    }

    private function canAccessApplicantDocuments(Request $request, Applicant $applicant): bool
    {
        $user = $request->user();
        return $user->hasRole(UserRole::ADMIN)
            || ($user->hasRole(UserRole::APPLICANT) && $applicant->user_id === $user->id);
    }
}
