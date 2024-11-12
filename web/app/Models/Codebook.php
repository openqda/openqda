<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Codebook extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'name',
        'description',
        'project_id',
        'properties',
        'creating_user_id',
        'code_order',
    ];

    /**
     * The attributes that are excluded from the audit.
     *
     * @var array
     */
    protected $auditExclude = [
        'code_order',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    protected $withCount = ['codes'];

    protected $dispatchesEvents = [
        'deleting' => \App\Events\CodebookDeleting::class,
    ];

    /**
     * Get the project to which the source belongs.
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function codes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Code::class);
    }

    /**
     * Get the user who created the codebook.
     */
    public function creatingUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'creating_user_id');
    }

    /**
     * Accessor for 'public' attribute.
     */
    public function getPublicAttribute()
    {
        return $this->properties['sharedWithPublic'] ?? false;
    }

    /**
     * Accessor for 'teams' attribute.
     */
    public function getTeamsAttribute()
    {
        return $this->properties['sharedWithTeams'] ?? false;
    }

    /**
     * Set the code order in the 'properties' JSON column.
     */
    public function setCodeOrder(array $order): void
    {
        $properties = $this->properties ?? [];
        $properties['code_order'] = $order;
        $this->properties = $properties;
        $this->save();
    }

    /**
     * Get the code order from the 'properties' JSON column.
     */
    public function getCodeOrder(): array
    {
        return $this->properties['code_order'] ?? [];
    }

    /**
     * Get the ordered list of codes based on the code_order in properties.
     */
    public function getOrderedCodes(): array
    {
        $order = $this->getCodeOrder();

        // Fetch codes from the database in the given order
        $codes = $this->codes()->whereIn('id', array_column($order, 'id'))->get()->keyBy('id');

        // Return ordered codes based on the stored order
        return array_map(fn ($item) => $codes->get($item['id']), $order);
    }

    /**
     * Update the code order in the 'properties' JSON column.
     */
    public function updateCodeOrder(array $newOrder): void
    {
        // Ensure properties is initialized as an array
        $properties = $this->properties ?? [];

        // Update the 'code_order' key
        $properties['code_order'] = $newOrder;

        // Save only the updated properties to the database
        $this->properties = $properties;
        $this->save();
    }
}
