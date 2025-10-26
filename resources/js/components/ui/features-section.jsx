import React from 'react';

export function FeaturesSection() {
  return (
    <section className="bg-white py-16 px-6">
      <div className="max-w-7xl mx-auto">
        {/* Our Features Pill Button */}
        <div className="mb-8">
          <div className="inline-block bg-white border-2 border-black rounded-full px-6 py-3">
            <span className="text-[#004A30] font-bold text-md">Our Features</span>
          </div>
        </div>

        {/* Main Title */}
        <div className="mb-16">
          <h2 className="text-4xl md:text-6xl font-bold text-black leading-tight">
            DISCOVER WHAT MAKES HYDRONEW<br />
            POWERFUL AND{' '}
            <span className="bg-[#D7E7BA] px-4 py-2 rounded-full">SUSTAINABLE</span>
          </h2>
        </div>

        {/* Three Feature Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {/* Card 1: PLANT HEALTH */}
          <div className="bg-[#D7E7BA] rounded-2xl p-8 relative overflow-hidden">
            {/* White Title Box */}
            <div className="bg-white rounded-xl px-4 py-3 mb-6">
              <h3 className="font-bold text-black text-lg">PLANT HEALTH</h3>
            </div>
            <p className="text-black text-sm leading-relaxed">
              MONITOR PLANT GROWTH AT EVERY STAGE—FROM SEEDLING TO HARVEST-READY—WITH LIVE DATA AND DYNAMIC VISUALS. GET INSIGHTS INTO PLANT AGE, HEALTH STATUS, AND ESTIMATED HARVEST TIME FOR SMARTER FARMING DECISIONS.
            </p>
            {/* Circular pattern inside card */}
            <div className="absolute -bottom-8 -right-8 w-24 h-24 bg-green-200 rounded-full opacity-50"></div>
          </div>

          {/* Card 2: WATER MONITORING */}
          <div className="bg-[#D7E7BA] rounded-2xl p-8 relative overflow-hidden">
            {/* White Title Box */}
            <div className="bg-white rounded-xl px-4 py-3 mb-6">
              <h3 className="font-bold text-black text-lg">WATER MONITORING</h3>
            </div>
            <p className="text-black text-sm leading-relaxed">
              TRACK WATER QUALITY IN REAL-TIME WITH IOT SENSORS MEASURING PH, TURBIDITY, AND WATER SAFETY INDICATORS. ENSURE EVERY DROP OF TREATED WASTEWATER IS CLEAN, EFFICIENT, AND OPTIMIZED FOR HYDROPONIC FARMING.
            </p>
            {/* Circular pattern inside card */}
            <div className="absolute -bottom-8 -right-8 w-24 h-24 bg-green-200 rounded-full opacity-50"></div>
          </div>

          {/* Card 3: SMART FILTRATION */}
          <div className="bg-[#D7E7BA] rounded-2xl p-8 relative overflow-hidden">
            {/* White Title Box */}
            <div className="bg-white rounded-xl px-4 py-3 mb-6">
              <h3 className="font-bold text-black text-lg">SMART FILTRATION</h3>
            </div>
            <p className="text-black text-sm leading-relaxed">
              EXPERIENCE MULTI-LAYERED FILTRATION COMBINING NATURAL FILTERS, UV STERILIZATION, AND MICROBIAL FUEL CELLS. ACHIEVE PURIFIED WATER WHILE GENERATING ENERGY FOR SYSTEM OPERATIONS.
            </p>
            {/* Circular pattern inside card */}
            <div className="absolute -bottom-8 -right-8 w-24 h-24 bg-green-200 rounded-full opacity-50"></div>
          </div>
        </div>
      </div>
    </section>
  );
}
