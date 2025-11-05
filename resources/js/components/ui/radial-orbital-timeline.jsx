"use client";;
import { useState, useEffect, useRef } from "react";
import { ArrowRight, Link, Zap } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export default function RadialOrbitalTimeline({
  timelineData
}) {
  const [expandedItems, setExpandedItems] = useState({});
  const [viewMode, setViewMode] = useState("orbital");
  const [rotationAngle, setRotationAngle] = useState(0);
  const [autoRotate, setAutoRotate] = useState(true);
  const [pulseEffect, setPulseEffect] = useState({});
  const [centerOffset, setCenterOffset] = useState({
    x: 0,
    y: 0,
  });
  const [activeNodeId, setActiveNodeId] = useState(null);
  const containerRef = useRef(null);
  const orbitRef = useRef(null);
  const nodeRefs = useRef({});

  const handleContainerClick = (e) => {
    if (e.target === containerRef.current || e.target === orbitRef.current) {
      setExpandedItems({});
      setActiveNodeId(null);
      setPulseEffect({});
      setAutoRotate(true);
    }
  };

  const toggleItem = (id) => {
    setExpandedItems((prev) => {
      const newState = { ...prev };
      Object.keys(newState).forEach((key) => {
        if (parseInt(key) !== id) {
          newState[parseInt(key)] = false;
        }
      });

      newState[id] = !prev[id];

      if (!prev[id]) {
        setActiveNodeId(id);
        setAutoRotate(false);

        const relatedItems = getRelatedItems(id);
        const newPulseEffect = {};
        relatedItems.forEach((relId) => {
          newPulseEffect[relId] = true;
        });
        setPulseEffect(newPulseEffect);

        centerViewOnNode(id);
      } else {
        setActiveNodeId(null);
        setAutoRotate(true);
        setPulseEffect({});
      }

      return newState;
    });
  };

  useEffect(() => {
    let rotationTimer;

    if (autoRotate && viewMode === "orbital") {
      rotationTimer = setInterval(() => {
        setRotationAngle((prev) => {
          const newAngle = (prev + 0.3) % 360;
          return Number(newAngle.toFixed(3));
        });
      }, 50);
    }

    return () => {
      if (rotationTimer) {
        clearInterval(rotationTimer);
      }
    };
  }, [autoRotate, viewMode]);

  const centerViewOnNode = (nodeId) => {
    if (viewMode !== "orbital" || !nodeRefs.current[nodeId]) return;

    const nodeIndex = timelineData.findIndex((item) => item.id === nodeId);
    const totalNodes = timelineData.length;
    const targetAngle = (nodeIndex / totalNodes) * 360;

    setRotationAngle(270 - targetAngle);
  };

  const calculateNodePosition = (index, total) => {
    const angle = ((index / total) * 360 + rotationAngle) % 360;
    // Responsive radius - smaller on mobile
    const radius = typeof window !== 'undefined' 
      ? window.innerWidth < 640 ? 180 
        : window.innerWidth < 768 ? 220 
        : window.innerWidth < 1024 ? 280 
        : 320
      : 320;
    const radian = (angle * Math.PI) / 180;

    const x = radius * Math.cos(radian) + centerOffset.x;
    const y = radius * Math.sin(radian) + centerOffset.y;

    const zIndex = Math.round(100 + 50 * Math.cos(radian));
    const opacity = Math.max(0.4, Math.min(1, 0.4 + 0.6 * ((1 + Math.sin(radian)) / 2)));

    return { x, y, angle, zIndex, opacity };
  };

  const getRelatedItems = itemId => {
    const currentItem = timelineData.find((item) => item.id === itemId);
    return currentItem ? currentItem.relatedIds : [];
  };

  const isRelatedToActive = itemId => {
    if (!activeNodeId) return false;
    const relatedItems = getRelatedItems(activeNodeId);
    return relatedItems.includes(itemId);
  };

  const getStatusStyles = status => {
    switch (status) {
      case "completed":
        return "text-white bg-green-600 border-green-600";
      case "in-progress":
        return "text-white bg-blue-600 border-blue-600";
      case "pending":
        return "text-gray-700 bg-gray-200 border-gray-300";
      default:
        return "text-gray-700 bg-gray-200 border-gray-300";
    }
  };

  return (
    <div className="w-full px-6 sm:px-6 lg:px-8">
      <div className="max-w-7xl mx-auto">
        <div className="mb-6 mt-12 px-6">
          <header
            className="flex flex-col gap-6 border-b border-neutral-900/10 pb-6 transition-colors duration-500 md:flex-row md:items-end md:justify-between dark:border-white/10">
            <div className="flex flex-col gap-2">
              <span
                className="text-sm sm:text-md uppercase tracking-[0.35em] text-neutral-500 transition-colors duration-500 dark:text-white/40">
                PROCESS
              </span>
              <h2 style={{ fontFamily: '"Viaoda Libre", serif' }}
                className="text-3xl font-black tracking-tight text-neutral-900 transition-colors duration-500 md:text-5xl dark:text-white">
                How Does It Work?          
              </h2>
            </div>
          </header>
        </div>

    <div
      className="w-full min-h-[600px] sm:min-h-[700px] md:min-h-[800px] lg:h-screen flex flex-col items-center justify-center bg-white overflow-hidden py-8"
      ref={containerRef}
      onClick={handleContainerClick}>
      <div
        className="relative w-full max-w-7xl h-full flex items-center justify-center px-4">
        <div
          className="absolute w-full h-full flex items-center justify-center"
          ref={orbitRef}
          style={{
            perspective: "1000px",
            transform: `translate(${centerOffset.x}px, ${centerOffset.y}px)`,
          }}>
          <div
            className="absolute w-16 h-16 sm:w-20 sm:h-20 md:w-24 md:h-24 rounded-full bg-primary flex items-center justify-center z-10">
            <div
              className="absolute w-20 h-20 sm:w-24 sm:h-24 md:w-32 md:h-32 rounded-full border border-green-300/40 animate-ping opacity-70"></div>
            <div
              className="absolute w-24 h-24 sm:w-28 sm:h-28 md:w-40 md:h-40 rounded-full border border-blue-300/30 animate-ping opacity-50"
              style={{ animationDelay: "0.5s" }}></div>
            <div className="w-8 h-8 sm:w-10 sm:h-10 md:w-12 md:h-12 rounded-full bg-white backdrop-blur-md shadow-lg"></div>
          </div>

          <div className="absolute w-[360px] h-[360px] sm:w-[440px] sm:h-[440px] md:w-[560px] md:h-[560px] lg:w-[640px] lg:h-[640px] rounded-full border border-gray-200"></div>

          {timelineData.map((item, index) => {
            const position = calculateNodePosition(index, timelineData.length);
            const isExpanded = expandedItems[item.id];
            const isRelated = isRelatedToActive(item.id);
            const isPulsing = pulseEffect[item.id];
            const Icon = item.icon;

            const nodeStyle = {
              transform: `translate(${position.x}px, ${position.y}px)`,
              zIndex: isExpanded ? 200 : position.zIndex,
              opacity: isExpanded ? 1 : position.opacity,
            };

            return (
              <div
                key={item.id}
                ref={(el) => (nodeRefs.current[item.id] = el)}
                className="absolute transition-all duration-700 cursor-pointer"
                style={nodeStyle}
                onClick={(e) => {
                  e.stopPropagation();
                  toggleItem(item.id);
                }}>
                <div
                  className={`absolute rounded-full -inset-1 ${
                    isPulsing ? "animate-pulse duration-1000" : ""
                  }`}
                  style={{
                    background: `radial-gradient(circle, rgba(147,51,234,0.15) 0%, rgba(147,51,234,0) 70%)`,
                    width: `${item.energy * 0.6 + 56}px`,
                    height: `${item.energy * 0.6 + 56}px`,
                    left: `-${(item.energy * 0.6 + 56 - 56) / 2}px`,
                    top: `-${(item.energy * 0.6 + 56 - 56) / 2}px`,
                  }}></div>
                <div
                  className={`
                  w-10 h-10 sm:w-12 sm:h-12 md:w-14 md:h-14 rounded-full flex items-center justify-center
                  ${
                    isExpanded
                      ? "bg-green-600 text-white"
                      : isRelated
                      ? "bg-green-400 text-white"
                      : "bg-gray-800 text-white"
                  }
                  border-2 
                  ${
                    isExpanded
                      ? "border-green-600 shadow-lg shadow-green-300/50"
                      : isRelated
                      ? "border-green-400 animate-pulse"
                      : "border-gray-600"
                  }
                  transition-all duration-300 transform
                  ${isExpanded ? "scale-125 sm:scale-150" : ""}
                `}>
                  <Icon size={typeof window !== 'undefined' && window.innerWidth < 640 ? 16 : 22} />
                </div>
                <div
                  className={`
                  absolute top-12 sm:top-14 md:top-16 whitespace-nowrap
                  text-xs sm:text-sm font-semibold tracking-wider
                  transition-all duration-300
                  ${isExpanded ? "text-gray-900 scale-110 sm:scale-125" : "text-gray-700"}
                `}>
                  {item.title}
                </div>
                {isExpanded && (
                  <Card
                    className="absolute top-20 sm:top-24 md:top-28 left-1/2 -translate-x-1/2 w-[90vw] max-w-[320px] sm:max-w-sm md:max-w-md lg:w-80 bg-white/95 backdrop-blur-lg border-gray-300 shadow-xl shadow-gray-400/20 overflow-visible">
                    <div
                      className="absolute -top-4 left-1/2 -translate-x-1/2 w-px h-4 bg-gray-400"></div>
                    <CardHeader className="pb-2 sm:pb-3">
                      <CardTitle className="text-sm sm:text-base mt-2 text-gray-900">
                        {item.title}
                      </CardTitle>
                    </CardHeader>
                    <CardContent className="text-xs sm:text-sm text-gray-700">
                      <p>{item.content}</p>
                      
                      {item.relatedIds.length > 0 && (
                        <div className="mt-4 sm:mt-5 pt-3 sm:pt-4 border-t border-gray-200">
                          <div className="flex items-center mb-2 sm:mb-3">
                            <Link size={12} className="text-gray-600 mr-1.5 sm:hidden" />
                            <Link size={14} className="text-gray-600 mr-1.5 hidden sm:block" />
                            <h4 className="text-xs sm:text-sm uppercase tracking-wider font-medium text-gray-600">
                              Next Step
                            </h4>
                          </div>
                          <div className="flex flex-wrap gap-1.5">
                            {item.relatedIds.map((relatedId) => {
                              const relatedItem = timelineData.find((i) => i.id === relatedId);
                              return (
                                <Button
                                  key={relatedId}
                                  variant="outline"
                                  size="sm"
                                  className="flex items-center h-7 sm:h-8 px-2 sm:px-3 py-0 text-xs sm:text-sm rounded-none border-gray-300 bg-transparent hover:bg-gray-100 text-gray-700 hover:text-gray-900 transition-all"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    toggleItem(relatedId);
                                  }}>
                                  {relatedItem?.title}
                                  <ArrowRight size={10} className="ml-1 sm:ml-1.5 text-gray-500 sm:hidden" />
                                  <ArrowRight size={12} className="ml-1.5 text-gray-500 hidden sm:block" />
                                </Button>
                              );
                            })}
                          </div>
                        </div>
                      )}
                    </CardContent>
                  </Card>
                )}
              </div>
            );
          })}
        </div>
      </div>
    </div>
      </div>
    </div>
  );
}
