<?php

namespace App\Models;

class objectT extends Model
{
    protected $table = 'object';
    protected $primaryKey = 'object_id';
    protected $fillable = [
        'object_id',
        'object_client_company_id',
        'object_address_id',
        'object_name',
        'object_key_name',
        'object_is_deleted',
        'object_is_hidden',
        'object_created_by',
        'object_created_at',
        'object_updated_at',
    ];

    public $timestamps = false;

    //join data as sibling
    public function address()
    {
        return $this->belongsTo(address::class, 'object_address_id', 'address_id', null);
    }

    //use query base
    public function contact()
    {
        return $this->select()
            ->join("address", "address_id", "=", "object_address_id")
            ->where('object_settings_type', 'contact')
            ->where('object_settings_value', 'manager')
            ->with('client')
            ->limit(1);
    }
}
