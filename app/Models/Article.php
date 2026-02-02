<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'articles';

    protected $fillable = [
        'author_id',
        'isContributor',
        'contributor',
        'contact',
        'category_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'scheduled_publish_time',
        'status', // draft, published, archived
        'isApproved',
        'isCommentable',
        'isPremium',
        'cover_image_url',
        'summary',
        'published_at',
        'isNotify',
    ];

    protected $dates = ['published_at'];

    protected $casts = [
        'isContributor' => 'boolean',
        'isApproved' => 'boolean',
        'isCommentable' => 'boolean',
        'isPremium' => 'boolean',
        'isNotify' => 'boolean',
    ];

    /* =======================
     | Relationships
     ======================= */

    // Author of the article
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // Contributor of the article (nullable FK)
    public function contributorUser()
    {
        return $this->belongsTo(User::class, 'contributor');
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

    /* =======================
     | Scopes
     ======================= */

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /* =======================
     | Notifications
     ======================= */
     public function notifications()
{
    return $this->morphMany(Notification::class, 'notifiable');
}

    /* =======================
     | Serialization
     ======================= */

    public function toArray()
    {
        $array = parent::toArray();

        $array['published_at'] = $this->published_at
            ? Carbon::parse($this->published_at)->format('F j, Y')
            : null;

        $array['scheduled_publish_time'] = $this->scheduled_publish_time
            ? Carbon::parse($this->scheduled_publish_time)->format('F j, Y')
            : null;

        return $array;
    }
}
