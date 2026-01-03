const shapefileLayers = [
    { 
        id: "counties", 
        name: "US Counties", 
        filename: "cb_2018_us_county_20m (2).zip", 
        loadOnStart: false,
        style: { fill: false, weight: 1, opacity: 0.6, color: "#888888" }
    },
    { 
        id: "states", 
        name: "US State Boundaries", 
        filename: "cb_2018_us_state_20m.zip", 
        loadOnStart: true,
        style: { fill: false, weight: 3, opacity: 1, color: "#000000" }
    }
];

const baseUrl = "";
