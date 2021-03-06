<?php

namespace App\Models;

use App\Events\Replied;
use App\Filters\ThreadFilters;
use App\Traits\HasActivity;
use App\Utilities\Markdown;
use App\Utilities\Regex;
use App\Utilities\Trending;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Thread extends Model
{
    use HasFactory, HasActivity, Searchable;

    protected $guarded = [];

    protected $casts = ['locked' => 'boolean'];

    protected $with = ['user', 'category'];

    public static function booted()
    {
        static::addGlobalScope('REPLIES_COUNT', function($builder) {
            $builder->withCount('replies');
        });

        static::deleting(function($thread) {
            $thread->replies->each->delete();
        });
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subscribers()
    {
        return $this->belongsToMany(User::class, 'subscriptions');
    }

    public function path()
    {
        return route('threads.show', [$this->category->slug, $this->id]);
    }

    public function createReply($reply)
    {
        $reply = $this->replies()->create($reply);

        event(new Replied($reply));

        $this->touch();

        return $reply;
    }

    public function subscribe()
    {
        return $this->subscribers()->attach(auth()->id());
    }

    public function unsubscribe()
    {
        return $this->subscribers()->detach(auth()->id());
    }

    public function hasUpdates()
    {
        if (auth()->guest()) return false;

        $key = auth()->user()->visitedThreadCacheKey($this->id);

        if (! cache($key)) return false;

        return $this->updated_at->gt(cache($key));
    }

    public function isSolved()
    {
        return ! is_null($this->answer_id);
    }

    public function scopeFilter(Builder $query, ThreadFilters $filters)
    {
        return $filters->apply($query);
    }

    public function getIsSubscribedToAttribute()
    {
        return $this->subscribers()->wherePivot('user_id', auth()->id())->exists();
    }

    public function getVisitsAttribute()
    {
        return Trending::score($this->id);
    }

    public function setBodyAttribute($body)
    {
        $this->attributes['body'] =
            preg_replace(
                Regex::USER_MENTION,
                ' <a href="/$1">$1</a>',
                Markdown::parse($body)
            );
    }

    public function toSearchableArray()
    {
        return $this->only(['id', 'title', 'body', 'replies_count', 'created_at', 'updated_at']);
    }
}
