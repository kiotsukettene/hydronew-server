import React from 'react';
import { ArrowUpRight, ArrowDownRight } from 'lucide-react';

export function BenefitsSection() {
  return (
    <section className="bg-white py-16 px-6">
      <div className="max-w-7xl mx-auto">
        {/* Header Section */}
        <div className="mb-12">
          <div className="flex items-start justify-between mb-6">
            <h2 className="text-4xl md:text-6xl font-bold text-black">
              BENEFITS FOR USERS
            </h2>
            <div className="w-8 h-8 bg-gray-300 rounded flex items-center justify-center">
              <ArrowUpRight className="w-5 h-5 text-gray-600" />
            </div>
          </div>
          <p className="text-lg text-black max-w-2xl">
            THE GREENWORLD HAS CARRIED OUT MANY SUCCESSFUL PROJECTS FOR REFORESTATION AND BIODIVERSITY CONSERVATION.
          </p>
        </div>

        {/* Benefit Cards */}
        <div className="space-y-6">
          {/* Card 1: SAFE AND SUSTAINABLE WATER USE */}
          <div className="bg-gray-200 rounded-2xl p-8 flex items-center justify-between">
            <div className="flex-1">
              <h3 className="font-bold text-black text-xl mb-3">SAFE AND SUSTAINABLE WATER USE</h3>
              <p className="text-black text-sm leading-relaxed">
                Users Can Confidently Reuse Treated Greywater For Hydroponics, Reducing Reliance On Freshwater And Supporting Eco-Friendly Farming.
              </p>
            </div>
            <div className="w-12 h-12 bg-gray-300 border border-gray-400 rounded-full flex items-center justify-center ml-6">
              <ArrowDownRight className="w-6 h-6 text-gray-600" />
            </div>
          </div>

          {/* Card 2: REAL-TIME MONITORING & CONTROL */}
          <div className="bg-[#D6E5BE] rounded-2xl p-8 flex items-center justify-between">
            <div className="flex-1">
              <h3 className="font-bold text-black text-xl mb-3">REAL-TIME MONITORING & CONTROL</h3>
              <p className="text-black text-sm leading-relaxed">
                IoT Sensors And The Mobile App Provide Instant Access To PH, Turbidity, TDS, And Tank Levels, Helping Users Make Informed Decisions Anytime, Anywhere.
              </p>
            </div>
            <div className="w-12 h-12 bg-[#D6E5BE] border border-green-400 rounded-full flex items-center justify-center ml-6">
              <ArrowUpRight className="w-6 h-6 text-green-600" />
            </div>
          </div>

          {/* Card 3: IMPROVED CROP GROWTH & YIELD */}
          <div className="bg-gray-200 rounded-2xl p-8 flex items-center justify-between">
            <div className="flex-1">
              <h3 className="font-bold text-black text-xl mb-3">IMPROVED CROP GROWTH & YIELD</h3>
              <p className="text-black text-sm leading-relaxed">
                AI-Driven Predictions And Tips Guide Users In Nutrient Dosing And Water Quality Management, Leading To Healthier Plants And Higher Productivity.
              </p>
            </div>
            <div className="w-12 h-12 bg-gray-300 border border-gray-400 rounded-full flex items-center justify-center ml-6">
              <ArrowDownRight className="w-6 h-6 text-gray-600" />
            </div>
          </div>

          {/* Card 4: COST-EFFICIENT & LOW MAINTENANCE */}
          <div className="bg-gray-200 rounded-2xl p-8 flex items-center justify-between">
            <div className="flex-1">
              <h3 className="font-bold text-black text-xl mb-3">COST-EFFICIENT & LOW MAINTENANCE</h3>
              <p className="text-black text-sm leading-relaxed">
                The System Reduces Water Waste, Minimizes Manual Monitoring, And Integrates Affordable Components, Making It Practical And Economical For Everyday Users.
              </p>
            </div>
            <div className="w-12 h-12 bg-gray-300 border border-gray-400 rounded-full flex items-center justify-center ml-6">
              <ArrowDownRight className="w-6 h-6 text-gray-600" />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

