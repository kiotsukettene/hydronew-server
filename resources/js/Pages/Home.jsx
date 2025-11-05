import React from 'react'

import { HeroSection } from '@/components/ui/hero-section'

import { FooterSection } from '@/components/ui/footer-section'
import { SimpleHeader } from '@/components/ui/simple-header'
import AboutSection2 from '@/components/ui/about-us'
import { DestinationCard } from '@/components/ui/card-21'
import FeaturesSection from '@/components/ui/features-section'
import FAQ from '@/components/ui/faq'
import HowItWorks from '@/components/ui/how-it-works'

const Home = () => {

  return (
    <div className='w-full'>
      <SimpleHeader/>
      <HeroSection />
      <FeaturesSection />
      <AboutSection2/>
      <HowItWorks/>
      {/* DestinationCard section - currently no data provided */}
      {/* <div className="w-full px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
        <div className="max-w-7xl mx-auto">
          <DestinationCard/>
        </div>
      </div> */}
      <FAQ/>
      <FooterSection />
    </div>
  )
}

export default Home
