import React from 'react';
import { RotateCcw, Leaf, Sun, ChevronDown } from 'lucide-react';

export function HeroSection() {
  return (
    <section className="bg-white py-16 px-6">
      <div className="max-w-7xl mx-auto">
        {/* Hero Title Section */}
        <div className="relative text-center mb-16">
          {/* Decorative Icons */}
          <div className="absolute -top-8 -left-8 w-12 h-12 bg-[#D7E7BA] rounded-full flex items-center justify-center">
            <RotateCcw className="w-6 h-6 text-green-700" />
          </div>
          
          <div className="absolute top-4 -left-16 w-12 h-12 bg-[#004A30] rounded-full flex items-center justify-center">
            <Leaf className="w-6 h-6 text-white" />
          </div>
          
          <div className="absolute -top-8 -right-8 w-12 h-12 bg-[#D7E7BA] rounded-full flex items-center justify-center">
            <Sun className="w-6 h-6 text-green-700" />
          </div>
          
          <div className="absolute top-4 -right-16 w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center">
            <ChevronDown className="w-6 h-6 text-gray-600" />
          </div>

          {/* Main Title */}
          <h1 className="text-4xl md:text-6xl font-bold text-black leading-tight">
            SAFE WATER,{' '}
            <span className="bg-[#D7E7BA] px-4 py-2 rounded-full">HEALTHY</span>
            {' '}PLANTS, SMARTER FUTURE
          </h1>
        </div>

        {/* Five Panel Layout */}
        <div className="flex flex-col lg:flex-row gap-8 items-start">
          {/* Panel 1: Landscape Image */}
          <div className="w-full lg:w-1/5">
            <div className="aspect-square rounded-2xl overflow-hidden">
              <img 
                src="/images/hero/image1.png" 
                alt="Terraced agricultural fields with golden light" 
                className="w-full h-full object-cover"
              />
            </div>
          </div>

          {/* Panel 2: Text Boxes */}
          <div className="w-full lg:w-1/5 space-y-4">
            {/* Fresh Lettuce Card */}
            <div className="bg-gray-200 rounded-2xl p-6">
              <h3 className="font-bold text-black text-lg mb-2">FRESH LETTUCE HARVEST</h3>
              <p className="text-black text-sm">SUCCESSFULLY GROWN WITH SAFE, TREATED WATER.</p>
            </div>
            
            {/* Filtration Stages Card */}
            <div className="bg-[#D7E7BA] rounded-2xl p-6">
              <h3 className="font-bold text-black text-lg mb-2">3 FILTRATION STAGES</h3>
              <p className="text-black text-sm">MICROBIAL FUEL CELL, NATURAL FILTER, AND UV PURIFICATION FOR CLEANER GROWTH.</p>
            </div>
          </div>

          {/* Panel 3: Large Leaf with Overlay */}
          <div className="w-full lg:w-1/5">
            <div className="aspect-[3/5] rounded-2xl overflow-hidden relative">
              <img 
                src="/images/hero/image2.png" 
                alt="Large green leaf with prominent veins" 
                className="w-full h-full object-cover"
              />
              
              {/* Text Overlay */}
              <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-xl px-6 py-4 min-w-[200px]">
                <p className="font-bold text-black text-sm text-center leading-tight">
                  FROM<br />
                  WASTE TO<br />
                  HARVEST
                </p>
              </div>
            </div>
          </div>

          {/* Panel 4: Medium Leaf with Overlay */}
          <div className="w-full lg:w-1/5">
            <div className="aspect-[3/4] rounded-2xl overflow-hidden relative">
              <img 
                src="/images/hero/image3.png" 
                alt="Medium green leaf with vein structure" 
                className="w-full h-full object-cover"
              />
              
              {/* Text Overlay */}
              <div className="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-white rounded-xl px-4 py-2">
                <p className="font-bold text-black text-sm">AI MEETS HYDROPONICS.</p>
              </div>
            </div>
          </div>

          {/* Panel 5: AI Monitoring Card */}
          <div className="w-full lg:w-1/5">
            <div className="aspect-square bg-[#D7E7BA] rounded-2xl p-6 flex items-center justify-center">
              <p className="font-bold text-black text-lg text-center">AI-DRIVEN MONITORING</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
