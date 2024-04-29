<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Selection extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'text',
        'description',
        'creating_user_id',
        'modifying_user_id',
        'code_id',
        'source_id',
        'project_id',
        'start_position',
        'end_position'
    ];

    // remove values when saving audits
    protected $auditExclude = [
        'modifying_user_id',
    ];

    protected $casts = [

    ];

    /**
     * Get the code that contains the variable.
     */
    public function code(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Code::class, 'code_id');
    }

    /**
     * Get the code that contains the variable.
     */
    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Code::class, 'project_id');
    }

    /**
     * Get the source that owns the variable.
     */
    public function source(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    /**
     * Get the user who created the selection.
     */
    public function creatingUser()
    {
        return $this->belongsTo(User::class, 'creating_user_id');
    }

    /**
     * Get the user who last modified the selection.
     */
    public function modifyingUser()
    {
        return $this->belongsTo(User::class, 'modifying_user_id');
    }


}
