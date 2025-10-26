import React from 'react';

export function FooterSection() {
  return (
    <footer className="relative mt-16">
      {/* Curved Light Green Background */}
      <div className="relative bg-[#D6E5BE] overflow-hidden">
        {/* Curved top edge */}
        <div className="absolute -top-12 left-0 right-0 h-24 bg-[#D6E5BE] " 
             style={{
               clipPath: 'ellipse(100% 100% at 50% 0%)'
             }}>
        </div>
        
        {/* White circular outline at top */}
        <div className="absolute -top-6 left-1/2 transform -translate-x-1/2 w-12 h-12 border-4 border-white rounded-full bg-white z-20"></div>

        <div className="pt-20 pb-12 px-6">
          <div className="max-w-7xl mx-auto">
            {/* STAY CONNECTED WITH US Heading */}
            <div className="text-center mb-16">
              <h2 className="text-3xl md:text-4xl font-bold text-black">
                STAY CONNECTED WITH US
              </h2>
            </div>

            {/* Footer Content */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-16">
              {/* Left Section - Logo and Tagline */}
              <div className="space-y-6">
                <div className="flex items-center gap-4">
                  {/* Logo */}
                  <div className="flex items-center gap-2">
<img src="/images/Logo.png" alt="Logo" className="h-8" />
				</div>
                
                </div>
                
                {/* Tagline */}
                <div className="space-y-3">
                  <div className="flex items-center gap-3">
                    <span className="bg-[#4F8331] text-white px-4 py-2 rounded-full text-sm font-medium">Connecting</span>
                    <span className="text-black text-lg">Communities</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="text-black text-lg">Through</span>
                    <span className="bg-[#4F8331] text-white px-4 py-2 rounded-full text-sm font-medium">Clean Water</span>
                  </div>
                </div>
              </div>

              {/* Right Section - Navigation Links */}
              <div className="grid grid-cols-2 gap-12">
                {/* About Column */}
                <div>
                  <h3 className="font-bold text-black text-xl mb-6">About</h3>
                  <ul className="space-y-3">
                    <li><a href="#" className="text-black text-base hover:text-green-600 transition-colors">Our Mission</a></li>
                    <li><a href="#" className="text-black text-base hover:text-green-600 transition-colors">Vision</a></li>
                    <li><a href="#" className="text-black text-base hover:text-green-600 transition-colors">Who We Are</a></li>
                  </ul>
                </div>

                {/* Social Column */}
                <div>
                  <h3 className="font-bold text-black text-xl mb-6">Social</h3>
                  <ul className="space-y-3">
                    <li><a href="#" className="text-black text-base hover:text-green-600 transition-colors">Instagram</a></li>
                    <li><a href="#" className="text-black text-base hover:text-green-600 transition-colors">Facebook</a></li>
                    
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
