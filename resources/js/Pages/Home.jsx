import React from 'react'

import { HeroSection } from '@/components/ui/hero-section'

import { BenefitsSection } from '@/components/ui/benefits-section'
import { FooterSection } from '@/components/ui/footer-section'
import { SimpleHeader } from '@/components/ui/simple-header'
import AboutSection2 from '@/components/ui/about-us'
import { DestinationCard } from '@/components/ui/card-21'
import FeaturesSection from '@/components/ui/features-section'
import HowItWorks from '@/components/ui/how-it-works'
import FAQ from '@/components/ui/faq'

const Home = () => {

  return (
    <div className='px-48'>
      <SimpleHeader/>
      <HeroSection />
            <FeaturesSection />

      <AboutSection2/>
      {/* <HowItWorks/> */}
      {/* <BenefitsSection /> */}
      <DestinationCard/>
      <FAQ/>
      <FooterSection />

    </div>
  )
}

export default Home
