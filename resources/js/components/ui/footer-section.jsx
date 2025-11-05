import React from 'react';
import { Footer } from './footer';
import { Facebook, Mail } from 'lucide-react';

export function FooterSection() {
  return (
    <div className="px-24">
      <Footer
        logo={<img src="/images/Logo.png" alt="HydroNew Logo" className="size-32 object-contain" />}
      
        socialLinks={[
          {
            icon: <Facebook className="h-5 w-5" />,
            href: "https://facebook.com",
            label: "Facebook",
          },
          {
            icon: <Mail className="h-5 w-5" />,
            href: "mailto:contact@hydronew.com",
            label: "Gmail",
          },
        ]}
        mainLinks={[
          { href: "#features", label: "Features" },
          { href: "/about-us", label: "About Us" },
          { href: "#how-it-works", label: "How It Works" },
          { href: "/download", label: "Download" },
        ]}
        legalLinks={[
          { href: "/privacy-policy", label: "Privacy Policy" },
          { href: "/terms-of-service", label: "Terms of Service" },
        ]}
        copyright={{
          text: "© 2025 HydroNew",
          license: "All rights reserved",
        }}
      />
    </div>
    
  );
}
