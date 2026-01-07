# Reports API - Quick Reference Guide

## 🌾 Crop Analytics Endpoints

### 1. Crop Performance
**GET** `/api/v1/reports/crop-performance`

**Query Parameters:**
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)
- `crop_name` (optional): Filter by crop name
- `status` (optional): active, inactive, maintenance

**Returns:**
- Active setups with health status
- Growth stage distribution
- Health status trends
- Parameter compliance (pH, TDS)
- Current vs target parameters

---

### 2. Yield Summary
**GET** `/api/v1/reports/yield-summary`

**Query Parameters:**
- `date_from` (optional): Start date
- `date_to` (optional): End date
- `crop_name` (optional): Filter by crop

**Returns:**
- Total harvested setups
- Weight by crop type
- Grade distribution (selling/consumption/disposal)
- Average yield per setup
- Sellable yield percentage
- Waste percentage
- Month-over-month comparison

---

### 3. Crop Comparison
**GET** `/api/v1/reports/crop-comparison`

**Query Parameters:**
- `crop_names[]` (required): Array of crop names (min 2)
- `metric` (optional): weight, duration, quality (default: weight)

**Returns:**
- Side-by-side comparison
- Yield efficiency (weight per day)
- Quality grade percentages
- Average growth duration
- Success rate
- Best performing crop

---

## 💧 Water Quality Endpoints

### 4. Water Quality Historical
**GET** `/api/v1/reports/water-quality/historical`

**Query Parameters:**
- `system_type` (required): dirty_water, clean_water, hydroponics_water
- `date_from` (optional): Start date (defaults to 7 days ago)
- `date_to` (optional): End date (defaults to today)
- `interval` (optional): hourly, daily, weekly (default: daily)

**Returns:**
- Time-series data for all parameters
- Min/max/average per interval
- Statistical summary
- Out-of-range occurrences (for hydroponics)

---

### 5. Water Quality Trends
**GET** `/api/v1/reports/water-quality/trends`

**Query Parameters:**
- `system_type` (required): dirty_water, clean_water, hydroponics_water
- `parameter` (optional): ph, tds, ec, turbidity, temperature, humidity (default: ph)
- `days` (optional): 1-90 (default: 7)

**Returns:**
- Daily trend data for parameter
- Target range (for hydroponics)
- Trend analysis (improving/stable/declining)
- Current vs historical average
- Deviation count
- Recommendations

---

## 🔬 Treatment Performance Endpoints

### 6. Treatment Performance
**GET** `/api/v1/reports/treatment-performance`

**Query Parameters:**
- `device_id` (required): Device ID
- `date_from` (optional): Start date (defaults to 30 days ago)
- `date_to` (optional): End date (defaults to today)

**Returns:**
- Total cycles
- Success/failure rates
- Average duration
- Stage-by-stage efficiency
- Average quality improvements
- Failure analysis
- Performance score (0-100)

---

### 7. Treatment Efficiency
**GET** `/api/v1/reports/treatment-efficiency`

**Query Parameters:**
- `device_id` (required): Device ID
- `days` (optional): 1-90 (default: 30)

**Returns:**
- Water quality improvement metrics
- Daily cycle trends
- Success rate trends
- Average cycles per day
- Efficiency trend (improving/stable/declining)
- Recent success rate
- Maintenance recommendations

---

## 🔐 Authentication

All endpoints require:
- `Authorization: Bearer {token}` header
- User must be authenticated via Sanctum
- Email must be verified

---

## 📊 Common Response Format

```json
{
  "status": "success",
  "data": {
    // Endpoint-specific data
  },
  "meta": {
    "date_range": {
      "from": "2026-01-01",
      "to": "2026-01-31"
    },
    "filters_applied": {},
    "total_records": 50,
    "generated_at": "2026-01-07T10:30:00Z"
  }
}
```

---

## 🚦 Status Codes

- `200` - Success
- `401` - Unauthorized (missing/invalid token)
- `404` - Resource not found (e.g., no sensor system)
- `422` - Validation error (invalid parameters)
- `500` - Server error

---

## 💡 Usage Tips

### For Real-time Monitoring
Use water quality trends with `days=1` for today's data:
```
GET /api/v1/reports/water-quality/trends?system_type=hydroponics_water&parameter=ph&days=1
```

### For Weekly Reports
Use yield summary with last 7 days:
```
GET /api/v1/reports/yield-summary?date_from=2026-01-01&date_to=2026-01-07
```

### For Performance Optimization
Check treatment efficiency for last 7 days:
```
GET /api/v1/reports/treatment-efficiency?device_id=1&days=7
```

### For Crop Planning
Compare multiple crops to find best performer:
```
GET /api/v1/reports/crop-comparison?crop_names[]=Lettuce&crop_names[]=Basil&crop_names[]=Spinach&metric=quality
```

---

## 📈 Data Insights

### Growth Stages
- `seedling` - Early growth phase
- `vegetative` - Active leaf growth
- `flowering` - Flowering phase
- `harvest-ready` - Ready for harvest

### Health Status
- `good` - All parameters within range
- `moderate` - Some parameters need attention
- `poor` - Multiple parameters out of range

### Treatment Stages
- `MFC` - Microbial Fuel Cell
- `Natural Filter` - Biological filtration
- `UV Filter` - UV sterilization
- `Clean Water Tank` - Final stage

---

## 🔍 Troubleshooting

### No Data Returned
- Check if user has any active setups/devices
- Verify date ranges are correct
- Ensure system_type exists for the user

### Out of Range Alerts
- High deviation count indicates parameter issues
- Check recommendations in trend analysis
- Review maintenance suggestions in treatment efficiency

### Low Performance Scores
- Review failure analysis for common issues
- Check stage efficiency for problem areas
- Consider maintenance if efficiency declining

