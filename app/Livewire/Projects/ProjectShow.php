<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app.sidebar')]
#[Title('Project Details')]
class ProjectShow extends Component
{
    use WithFileUploads;

    public ?Project $project = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $newAttachments = [];

    public bool $showPreviewModal = false;

    public string $previewUrl = '';

    public string $previewFilename = '';

    public string $previewMime = '';

    public string $newComment = '';

    public function mount(int $id): void
    {
        $this->project = Project::with(['attachments', 'comments.user'])->findOrFail($id);
    }

    public function saveAttachments(): void
    {
        $this->validate([
            'newAttachments.*' => ['file', 'max:10240'],
        ]);

        foreach ($this->newAttachments as $file) {
            $path = $file->store('projects', 'public');
            $this->project->attachments()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->newAttachments = [];
        $this->project->load('attachments');
        Flux::toast(variant: 'success', text: __('Attachments uploaded successfully.'));
    }

    public function removeAttachment(int $attachmentId): void
    {
        $attachment = $this->project->attachments()->find($attachmentId);
        if ($attachment) {
            Storage::disk('public')->delete($attachment->path);
            $attachment->delete();
            $this->project->load('attachments');
            Flux::toast(variant: 'success', text: __('Attachment deleted successfully.'));
        }
    }

    public function openPreview(string $url, string $filename, string $mime): void
    {
        $this->previewUrl = $url;
        $this->previewFilename = $filename;
        $this->previewMime = $mime;
        $this->showPreviewModal = true;
    }

    public function addComment(): void
    {
        $this->validate([
            'newComment' => ['required', 'string', 'max:5000'],
        ]);

        $this->project->comments()->create([
            'user_id' => auth()->id(),
            'body' => $this->newComment,
        ]);

        $this->newComment = '';
        $this->project->load('comments.user');
        Flux::toast(variant: 'success', text: __('Comment added successfully.'));
    }

    public function deleteComment(int $commentId): void
    {
        $comment = $this->project->comments()->find($commentId);
        if ($comment && $comment->user_id === auth()->id()) {
            $comment->delete();
            $this->project->load('comments.user');
            Flux::toast(variant: 'success', text: __('Comment deleted successfully.'));
        }
    }

    public function render()
    {
        return view('livewire.projects.project-show', [
            'statuses' => Project::statuses(),
            'types' => Project::types(),
            'operators' => Project::operators(),
        ]);
    }
}
