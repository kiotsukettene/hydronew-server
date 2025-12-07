<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
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

    public function logs()
    {
        return $this->hasMany(HydroponicSetupLog::class, 'hydroponic_setup_id');
    }

    /**
     * Scope filter for search and date filtering
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    
    public function scopeFilter($query, array $filters)
{
    // Search by crop name
    if ($filters['search'] ?? false) {
        $query->where('crop_name', 'like', '%' . $filters['search'] . '%');
    }

    // Filter by month
    if ($filters['month'] ?? false) {
        $dateType = $filters['date_type'] ?? 'harvest'; // 'setup' or 'harvest'
        $month = $filters['month'];
        
        // Check if it's just a month number (1-12) or full YYYY-MM format
        if (preg_match('/^\d{1,2}$/', $month)) {
            // It's just a month number, use current year
            $year = date('Y');
            $month = str_pad($month, 2, '0', STR_PAD_LEFT); // Pad to 2 digits
        } elseif (preg_match('/^\d{4}-\d{2}$/', $month)) {
            // It's already in YYYY-MM format
            $year = substr($month, 0, 4);
            $month = substr($month, 5, 2);
        } else {
            // Invalid format, skip filtering
            return $query;
        }
        
        // Get first and last day of the month
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
        
        if ($dateType === 'setup') {
            // Filter by setup_date for the entire month (use date format for datetime column)
            $query->whereDate('setup_date', '>=', $startDate->toDateString())
                  ->whereDate('setup_date', '<=', $endDate->toDateString());
        } else {
            // Filter by harvest_date for the entire month (use date comparison for date column)
            $query->whereYear('harvest_date', '=', $year)
                  ->whereMonth('harvest_date', '=', $month);
        }
    }

    return $query;
}

    /**
     * Scope for harvested setups only
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHarvested($query)
    {
        return $query->where('harvest_status', 'harvested');
    }
}
