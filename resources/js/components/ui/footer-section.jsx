import React from 'react';

export function FooterSection() {
  return (
    <footer className="relative mt-12 sm:mt-16 lg:mt-20 w-full">
      {/* Curved Light Green Background */}
      <div className="relative overflow-hidden">
        {/* Curved top edge */}
        <div className="absolute -top-12 left-0 right-0 h-24" 
             style={{
               clipPath: 'ellipse(100% 100% at 50% 0%)'
             }}>
        </div>
        
        {/* White circular outline at top */}
        <div className="absolute -top-6 left-1/2 transform -translate-x-1/2 w-10 h-10 sm:w-12 sm:h-12 border-3 sm:border-4 border-white rounded-full bg-white z-20"></div>

        <div className="pt-16 sm:pt-20 pb-8 sm:pb-12 px-4 sm:px-6 lg:px-8">
          <div className="max-w-7xl mx-auto">
            

            {/* Footer Content */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 sm:gap-12 lg:gap-16">
              {/* Left Section - Logo and Tagline */}
              <div className="space-y-4 sm:space-y-6">
                <div className="flex items-center gap-4">
                  {/* Logo */}
                  <div className="flex items-center gap-2">
                    <img src="/images/Logo.png" alt="Logo" className="h-6 sm:h-8" />
                  </div>
                </div>
                
                {/* Tagline */}
                <div className="space-y-2 sm:space-y-3">
                  <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                    <span className="bg-[#4F8331] text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-medium">Connecting</span>
                    <span className="text-black text-base sm:text-lg">Communities</span>
                  </div>
                  <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                    <span className="text-black text-base sm:text-lg">Through</span>
                    <span className="bg-[#4F8331] text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-medium">Clean Water</span>
                  </div>
                </div>
              </div>

              {/* Right Section - Navigation Links */}
              <div className="grid grid-cols-2 gap-8 sm:gap-12">
                {/* About Column */}
                <div>
                  <h3 className="font-bold text-black text-lg sm:text-xl mb-4 sm:mb-6">About</h3>
                  <ul className="space-y-2 sm:space-y-3">
                    <li><a href="#" className="text-black text-sm sm:text-base hover:text-green-600 transition-colors">Our Mission</a></li>
                    <li><a href="#" className="text-black text-sm sm:text-base hover:text-green-600 transition-colors">Vision</a></li>
                    <li><a href="#" className="text-black text-sm sm:text-base hover:text-green-600 transition-colors">Who We Are</a></li>
                  </ul>
                </div>

                {/* Social Column */}
                <div>
                  <h3 className="font-bold text-black text-lg sm:text-xl mb-4 sm:mb-6">Social</h3>
                  <ul className="space-y-2 sm:space-y-3">
                    <li><a href="#" className="text-black text-sm sm:text-base hover:text-green-600 transition-colors">Instagram</a></li>
                    <li><a href="#" className="text-black text-sm sm:text-base hover:text-green-600 transition-colors">Facebook</a></li>
                  </ul>
                </div>
              </div>
            </div>

            {/* Copyright */}
            <div className="mt-16 pt-8 border-t border-green-200 text-center">
              <p className="text-gray-600 text-sm">
                © 2024 HydroNew. All rights reserved. Transforming wastewater into sustainable farming solutions.
              </p>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
