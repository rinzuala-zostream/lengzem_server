<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'articles';

    protected $fillable = [
        'author_id',
        'category_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'scheduled_publish_time',
        'status', // draft, published, archived
        'isCommentable',
        'cover_image_url',
        'summary',
        'published_at',
    ];

    protected $casts = [
        'isCommentable' => 'boolean',

    ];

    protected $dates = ['published_at'];

    // Article belongs to an Author
    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    // Article belongs to a Category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Article has many Comments
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // Article belongs to many Tags
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tags');
    }

    // Article has many Media items
    public function media()
    {
        return $this->hasMany(Media::class);
    }

    // Article has many Interactions (likes, bookmarks)
    public function interactions()
    {
        return $this->hasMany(Interaction::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function toArray()
    {
        $array = parent::toArray();

        // Format the fields
        $array['published_at'] = $this->published_at
            ? Carbon::parse($this->published_at)->format('F j, Y')
            : null;

        $array['scheduled_publish_time'] = $this->scheduled_publish_time
            ? Carbon::parse($this->scheduled_publish_time)->format('F j, Y')
            : null;

        return $array;
    }

}
