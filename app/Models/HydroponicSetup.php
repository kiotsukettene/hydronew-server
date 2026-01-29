<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HydroponicSetup
 *
 * @property int $id
 * @property int $user_id
 * @property string $bed_size
 * @property string $water_amount
 * @property Carbon|null $setup_date
 * @property string|null $status
 *
 * @property Collection|HydroponicYield[] $hydroponic_yields
 *
 * @package App\Models
 */
class HydroponicSetup extends Model
{
    use HasFactory;

    protected $table = 'hydroponic_setup';
    public $timestamps = false;

    protected $casts = [
        'user_id' => 'integer',
        'number_of_crops' => 'integer',
        'pump_config' => 'array',
        'target_ph_min' => 'float',
        'target_ph_max' => 'float',
        'target_tds_min' => 'float',
        'target_tds_max' => 'float',
        'setup_date' => 'datetime',
        'harvest_date' => 'date',
        'is_archived' => 'boolean',
    ];

    protected $fillable = [
        'user_id',
        'device_id',
        'crop_name',
        'number_of_crops',
        'bed_size',
        'pump_config',
        'nutrient_solution',
        'target_ph_min',
        'target_ph_max',
        'target_tds_min',
        'target_tds_max',
        'water_amount',
        'setup_date',
        'harvest_date',
        'harvest_status',
        'status',
        'growth_stage',
        'health_status',
        'is_archived',
    ];

    public function hydroponic_yields()
    {
        return $this->hasMany(HydroponicYield::class, 'hydroponic_setup_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    
    public function scopeFilter($query, array $filters)
{
    if ($filters['search'] ?? false) {
        $query->where('crop_name', 'like', '%' . $filters['search'] . '%');
    }

    if ($filters['month'] ?? false) {
        $dateType = $filters['date_type'] ?? 'harvest';
        $month = $filters['month'];
        
        if (preg_match('/^\d{1,2}$/', $month)) {
            $year = date('Y');
            $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        } elseif (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $year = substr($month, 0, 4);
            $month = substr($month, 5, 2);
        } else {
            return $query;
        }
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
        
        if ($dateType === 'setup') {
            $query->whereDate('setup_date', '>=', $startDate->toDateString())
                  ->whereDate('setup_date', '<=', $endDate->toDateString());
        } else {
            $query->whereYear('harvest_date', '=', $year)
                  ->whereMonth('harvest_date', '=', $month);
        }
    }

    return $query;
}

    public function scopeHarvested($query)
    {
        return $query->where('harvest_status', 'harvested');
    }
}
