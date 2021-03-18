<?php

namespace App\Models;

use App\Filters\ThreadFilters;
use App\Traits\HasActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Thread extends Model
{
    use HasFactory, HasActivity;

    protected $guarded = [];

    protected $with = ['user', 'category'];

    public static function booted()
    {
        static::addGlobalScope('repliesCount', function($builder) {
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

    public function subscriptions()
    {
        return $this->belongsToMany(User::class, 'subscriptions');
    }

    public function path()
    {
        return route('threads.show', [$this->category->slug, $this->id]);
    }

    public function addReply($reply)
    {
        return $this->replies()->create($reply);
    }

    public function subscribe()
    {
        return $this->subscriptions()->attach(auth()->id());
    }

    public function unsubscribe()
    {
        return $this->subscriptions()->detach(auth()->id());
    }

    public function scopeFilter(Builder $query, ThreadFilters $filters)
    {
        return $filters->apply($query);
    }

    public function getIsSubscribedToAttribute()
    {
        return $this->subscriptions()->wherePivot('user_id', auth()->id())->exists();
    }
}
