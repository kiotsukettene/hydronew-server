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
                        src="/images/preview.jpg" 
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
                      src="/images/mobile-dashboard.png" 
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

