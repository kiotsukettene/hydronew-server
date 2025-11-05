import React from 'react';
import { SimpleHeader } from '@/components/ui/simple-header';
import { FooterSection } from '@/components/ui/footer-section';
import { Button } from '@/components/ui/button';
import { Apple, Download as DownloadIcon } from 'lucide-react';

const Download = () => {
  return (
    <div className='w-full'>
      <SimpleHeader />
      
      {/* Download Section */}
      <section className="min-h-screen w-full relative overflow-hidden pt-32 sm:pt-40 lg:pt-48 pb-16 bg-gradient-to-br from-white via-green-50/30 to-lime-50/40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          
          

          {/* Main Content Grid */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            
            {/* Left Side - Text + Buttons */}
            <div className="space-y-8 lg:space-y-10">
              
              {/* Section Label */}
              <div>
               

                <div className=" mb-8 md:mb-10">
          <span className="inline-block rounded-full border px-3 py-1 text-xs font-medium tracking-wide text-neutral-600">DOWNLOAD THE APP</span>
        </div>
                
                {/* Main Heading */}
                <h1 className="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6" >
                  Monitor water quality{' '}
                  <span className="">anytime, anywhere.</span>
                </h1>
                
                {/* Subtext */}
                <p className="text-base sm:text-lg text-gray-600 leading-relaxed">
                  Access HydroNew right from your device and stay updated with real-time data on water filtration and hydroponics performance.
                </p>
              </div>

              {/* Download Buttons */}
              <div className="space-y-4">
                <div className="flex flex-col sm:flex-row gap-4">
                  {/* App Store Button */}
                  <a
                    href="#"
                    className="flex items-center justify-center gap-3 bg-black text-white px-6 py-3 rounded-full hover:bg-gray-800 transition-all duration-300 shadow-lg hover:shadow-xl group min-w-[200px]"
                  >
                    <Apple className="w-8 h-8" />
                    <div className="text-left">
                      <div className="text-xs opacity-90">Download on the</div>
                      <div className="text-lg font-semibold">App Store</div>
                    </div>
                  </a>

                  {/* Google Play Button */}
                  <a
                    href="#"
                    className="flex items-center justify-center gap-3 bg-black text-white px-6 py-4 rounded-full hover:bg-gray-800 transition-all duration-300 shadow-lg hover:shadow-xl group min-w-[200px]"
                  >
                    <svg className="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.53,12.9 20.18,13.18L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z" />
                    </svg>
                    <div className="text-left">
                      <div className="text-xs opacity-90">GET IT ON</div>
                      <div className="text-lg font-semibold">Google Play</div>
                    </div>
                  </a>
                </div>

                {/* Divider */}
                <div className="flex items-center gap-4 py-4">
                  <div className="flex-1 h-px bg-gray-300"></div>
                  <span className="text-sm text-gray-500 font-medium">or</span>
                  <div className="flex-1 h-px bg-gray-300"></div>
                </div>

                {/* QR Code Section */}
                <div className="bg-white rounded-2xl p-6 shadow-md border border-gray-100 inline-block">
                  <div className="flex flex-col sm:flex-row items-center gap-6">
                    <div className="bg-white p-3 rounded-xl border-2 border-gray-200">
                      <img 
                        src="/images/mock-qr.png" 
                        alt="QR Code" 
                        className="w-32 h-32 sm:w-40 sm:h-40"
                      />
                    </div>
                    <div className="text-center sm:text-left">
                      <p className="text-sm font-semibold text-gray-900 mb-1">
                        Scan to preview the app
                      </p>
                      <p className="text-xs text-gray-500">
                        Point your camera at the QR code
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Right Side - Phone Mockup */}
            <div className="relative flex items-center justify-center lg:justify-end">
              {/* Glow Effect */}
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="w-64 h-64 sm:w-80 sm:h-80 bg-green-300/40 rounded-full blur-3xl opacity-60"></div>
              </div>

              {/* Phone Mockup Container */}
              <div className="relative z-10 w-full max-w-sm">
                <div className="relative bg-gray-900 rounded-[3rem] p-3 shadow-2xl">
                  {/* Phone Notch */}
                  <div className="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-6 bg-gray-900 rounded-b-2xl z-10"></div>
                  
                  {/* Screen Content - Dashboard Image */}
                  <div className="bg-white rounded-[2.5rem] overflow-hidden">
                    <img 
                      src="/images/dashboard.png" 
                      alt="HydroNew Dashboard" 
                      className="w-full h-full object-cover object-top"
                    />
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </section>

      <FooterSection />
    </div>
  );
};

export default Download;

