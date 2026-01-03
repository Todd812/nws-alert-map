# nws-alert-map
Interactive real-time map of NWS weather alerts for all US zones, counties, and fire zones with color-coded severity and detailed alert modals for all alert types.

# NWS Weather Alerts Map

An interactive web map displaying real-time National Weather Service (NWS) active alerts across the United States, with smart zone highlighting, customizable alert colors, and advanced filtering.

# Install
Upload both files to your server.  Open https://yourdomain.com/weatheralerts.php in your browser.

## Live Demo

View Demo: https://www.stormsalert.com/dashboards/usweatherdashboard/allweatheralertsmap

## Basemap and NWS API codes

Feel free to change the basemap inside the code.  You MUST make sure to attribute correctly in order to use the basemap.  You are required to add in correct user-agent headers including your contact email for the NWS calls on line 1176.

## Features

- **Real-time NWS Alerts** – Fetches active alerts directly from the official NWS API (`api.weather.gov`)
- **Polygon & Zone-Based Alerts** – Shows both polygonal alerts and zone-based (UGC) alerts
- **Smart Zone Highlighting** – New alerts automatically highlight and briefly flash when a new alert is issued.  Expired alerts removed from the map on updates.
- **Customizable Alert Colors** – Users can change colors for any alert type and save preferences
- **Alert Filtering** – Toggle visibility of individual alert types (warnings, watches, advisories, etc.)
- **Persistent Settings** – Color and visibility preferences saved in browser localStorage
- **URL Overrides** – Use `?alerts=` parameter to show only specific alert types (great for embedded or focused views) (Must use NWS standard words commma separated like ?alerts=Severe Thunderstorm Warning,Winter Weather Advisory or as many different alert types as you want.  Default is all alerts.
- **State-Centric View** – Use `?state=CA` to center and zoom on a specific state
- **Responsive Design** – Works on desktop, tablet, and mobile

## Attribution

- **Attribution is Required** – Attribution to StormsAlert.com is required.  If you plan on using this map on a website please email me at webmaster@stormsalert.com. The zones file will be compiled monthly and is complex to complete.  Updates will change as the NWS makes changes to zones.

## Usage

### Basic View
Upload both files to your server and open the map file and see the NWS alerts.

### Focus on a Specific State
Append a state abbreviation:  
`https://yourdomain.com/weatheralerts.php?state=TX` → Centers on Texas

### Show Only Specific Alerts
Use the `alerts` parameter (comma-separated, exact alert names):  
`https://yourdomain.com/weatheralerts.php?alerts=Tornado Warning,Flash Flood Warning,Red Flag Warning`

Example: Only fire weather alerts  
`https://yourdomain.com/weatheralerts.php?alerts=Red Flag Warning,Fire Weather Watch,Extreme Fire Danger`

### Settings & Filters
Click the ⚙️ gear icon in the bottom-right to:
- Toggle alert types on/off
- Change colors for any alert
- Reset colors to defaults
- Toggle overlay layers (states, counties)

Settings are saved per browser.

## Data Sources

- **Alerts**: National Weather Service API – https://api.weather.gov/alerts/active
- **Zone Boundaries**: Pre-processed GeoJSON (cached for performance)
- **Base Map**: Esri World Dark Gray Base (Change to any Basemap)
- **State & County Boundaries**: U.S. Census Bureau TIGER/Line shapefiles (simplified)
