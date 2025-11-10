<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportInvoiceStatusLogs extends Model
{
    protected $fillable = [
        'note',
        'invoice_id',
        'status'
    ];
}
