<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Todo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        $filename = fake()->uuid().'.'.fake()->fileExtension();

        return [
            'todo_id' => Todo::factory(),
            'filename' => $filename,
            'path' => "todos/{$filename}",
            'mime_type' => fake()->mimeType(),
            'size' => fake()->numberBetween(1024, 10485760),
        ];
    }
}
