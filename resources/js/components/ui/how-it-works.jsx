import React from 'react'
import RadialOrbitalTimeline from './radial-orbital-timeline';
import { Activity, Droplet, Layers, Leaf, Recycle } from 'lucide-react';


const HowItWorks = () => {

const timelineData = [
  {
    id: 1,
    title: "Greywater Collection",
    content: "Greywater from wash vegetables and fruits as input for treatment.",
    category: "From Source",
    icon: Droplet,
    relatedIds: [2],
    
  },
  {
    id: 2,
    title: "Filtration Process",
    content: "The water passes through natural, microbial, and UV filters to remove impurities and pathogens.",
    category: "Multi-Stage Purification",
    icon: Layers,
    relatedIds: [3],
  },
  {
    id: 3,
    title: "HydroNew System",
    content: "Inside the system, treated water flows into hydroponic setups where plants grow efficiently.",
    category: "HydroNew in Action",
    icon: Leaf,
    relatedIds: [4],
    
  },
  {
    id: 4,
    title: "IoT Monitoring",
    content: "Sensors measure pH, turbidity, and water level in real-time — data is sent to your dashboard.",
    category: "Smart Data Tracking",
    icon: Activity,
    relatedIds: [5],
    
  },
  {
    id: 5,
    title: "Reuse and Recycle",
    content: "Purified water is recycled for continuous hydroponic use, saving resources and reducing waste.",
    category: "Clean Water, New Life",
    icon: Recycle,
    relatedIds: [1],
    
  },
];


  return (
    <div id="how-it-works">
            <RadialOrbitalTimeline timelineData={timelineData} />

      
    </div>
  )
}

export default HowItWorks
