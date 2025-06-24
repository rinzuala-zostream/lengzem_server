<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'published_at' => $this->published_at,
            'view_count' => $this->view_count,
            'cover_image' => $this->cover_image,
            'author' => $this->author,
            'category' => $this->category,
            'tags' => $this->tags, // includes pivot
        ];
    }

}
