<?php

namespace App\Models;

use App\Http\Controllers\Admin\TravelPackageController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'travel_packages_id', 'images',
    ];

    protected $hidden = [

    ];

    public function travel_package(){
        return $this->belongsTo(TravelPackage::class, 'travel_packages_id', 'id');
    }

}
