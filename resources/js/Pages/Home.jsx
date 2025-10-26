import React from 'react'
import { HydroNewNavbar } from '@/components/ui/hydronew-navbar'
import { HeroSection } from '@/components/ui/hero-section'
import { AboutUsSection } from '@/components/ui/about-us-section'
import { FeaturesSection } from '@/components/ui/features-section'
import { BenefitsSection } from '@/components/ui/benefits-section'
import { FooterSection } from '@/components/ui/footer-section'
import { SimpleHeader } from '@/components/ui/simple-header'
import AboutSection2 from '@/components/ui/about-us'
import { DestinationCard } from '@/components/ui/card-21'

const Home = () => {
    
  return (
    <div>
      <SimpleHeader/>
      <HeroSection />
      <AboutSection2/>
      <FeaturesSection />
      <BenefitsSection />
      <DestinationCard/>
      <FooterSection />
        
    </div>
  )
}

export default Home
