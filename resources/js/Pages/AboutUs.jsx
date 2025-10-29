import React from 'react';
import { ArrowRight, Download } from 'lucide-react';
import { HydroNewNavbar } from '@/components/ui/hydronew-navbar';

export default function AboutUs() {
  return (
    <div className="min-h-screen">
      <HydroNewNavbar currentPage="about" />

      {/* Hero Section with Background Image */}
      <section className="relative min-h-screen flex items-center">
        {/* Background Image */}
        <div className="absolute inset-0 z-0">
          <img
            src="/images/hero/about-us-bg.png"
            alt="Natural landscape background"
            className="w-full h-full object-cover"
          />
          {/* Overlay for better text readability */}
          <div className="absolute inset-0 bg-black bg-opacity-30"></div>
        </div>

        {/* Content */}
        <div className="relative z-10 w-full px-6">
          <div className="max-w-7xl mx-auto">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

              {/* Left Section - Main Content */}
              <div className="space-y-8">
                {/* Who We Are Button */}
                <div className="inline-block">
                  <button className="bg-black text-white px-6 py-3 rounded-lg flex items-center gap-2 hover:bg-gray-800 transition-colors">
                    <span>Who We Are</span>
                    <ArrowRight className="w-4 h-4" />
                  </button>
                </div>

                {/* Main Title */}
                <h1 className="text-4xl md:text-6xl font-bold text-white leading-tight">
                  Blending Technology with Sustainability
                </h1>

                {/* Description */}
                <p className="text-lg md:text-xl text-white leading-relaxed max-w-2xl">
                  We are Computer Science students committed to merging AI, IoT, and eco-technology to create sustainable farming solutions for the future.
                </p>
              </div>

              {/* Right Section - Download App */}
              <div className="flex justify-end">
                <div className="bg-gray-200 bg-opacity-90 backdrop-blur-sm rounded-2xl p-8 max-w-sm">
                  <h3 className="text-xl font-bold text-black mb-6">Download Our App</h3>

                  <button className="w-full bg-green-600 text-white px-6 py-4 rounded-lg flex items-center justify-center gap-3 hover:bg-green-700 transition-colors">
                    <Download className="w-5 h-5" />
                    <span className="font-medium">Download</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
