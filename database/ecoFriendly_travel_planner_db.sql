CREATE DATABASE IF NOT EXISTS ecoFriendly_travel_planner_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecoFriendly_travel_planner_db;

DROP TABLE IF EXISTS trips;
DROP TABLE IF EXISTS destinations;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE destinations (
    destination_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    location VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    eco_rating TINYINT NOT NULL CHECK (eco_rating BETWEEN 1 AND 5),
    eco_notes TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE trips (
    trip_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    destination_id INT NOT NULL,
    
    /* Travel Date Period */
    travel_date DATE NOT NULL,
    return_date DATE NULL, -- Allows for multi-day itineraries
    
    transport_mode VARCHAR(50) NOT NULL,
    distance_km DECIMAL(8,2) NOT NULL,
    
    /* Impact & Efficiency Tracking */
    traveler_count INT NOT NULL DEFAULT 1,
    sustainability_priority ENUM('carbon', 'balance', 'local') DEFAULT 'carbon',
    carbon_footprint_kg DECIMAL(10,2) NULL, 
    
    /* Planning & Notes */
    notes TEXT NULL,
    sustainability_score INT NOT NULL, 
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    /* Constraints */
    CONSTRAINT fk_trips_user FOREIGN KEY (user_id) 
        REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_trips_destination FOREIGN KEY (destination_id) 
        REFERENCES destinations(destination_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO destinations (name, location, description, eco_rating, eco_notes) VALUES
-- PHILIPPINES: LUZON
('Batanes Cultural Landscape', 'Batanes, Philippines', 'A scenic island destination known for protected landscapes, traditional communities, and low-density tourism.', 5, 'Respect local Ivatan culture, use locally owned homestays, and avoid single-use plastics during island tours.'),
('Baguio Green Routes', 'Baguio City, Philippines', 'A cooler upland city with walkable districts, parks, and opportunities for lower-impact local travel.', 4, 'Walk between city attractions when possible and support zero-waste cafes or refill stations.'),
('Intramuros Heritage Walk', 'Manila, Philippines', 'A historical destination best experienced through walking tours, museums, and local cultural stops.', 5, 'Walking is highly recommended to reduce traffic impact while exploring heritage sites.'),
('La Union Coastal Getaway', 'La Union, Philippines', 'A coastal destination that mixes beach tourism with local food and community enterprises.', 3, 'Use shared transport, bring reusable containers, and support small local businesses.'),
('Sagada Highland Retreat', 'Mountain Province, Philippines', 'A misty mountain town focused on indigenous culture, cave exploration, and organic farming.', 4, 'Hire local guides for all treks and respect sacred burial sites. Bring warm layers to reduce heater use.'),

-- PHILIPPINES: VISAYAS & PALAWAN
('Palawan Community Eco Tours', 'Palawan, Philippines', 'An island province known for biodiversity, marine ecosystems, and protected natural attractions.', 5, 'Follow marine sanctuary rules, do not touch corals, and choose boat tours with responsible operators.'),
('Bohol Chocolate Hills & Eco Trails', 'Bohol, Philippines', 'A popular destination combining geological heritage, eco-tours, and community-based attractions.', 4, 'Choose accredited guides, support eco-certified accommodations, and avoid wildlife disturbance.'),
('Cebu South Nature Route', 'Cebu, Philippines', 'A route featuring waterfalls, marine sites, and cultural stops with varied transport access.', 3, 'Combine shared van routes with local public transport and avoid littering in natural areas.'),
('Iloilo River and Heritage Circuit', 'Iloilo, Philippines', 'A city-and-region itinerary with river esplanades, heritage spaces, and food destinations.', 4, 'Use cycling paths where available and choose local food options with sustainable packaging.'),
('Apo Island Marine Sanctuary', 'Negros Oriental, Philippines', 'A community-managed marine reserve famous for sea turtle sightings and vibrant coral gardens.', 5, 'Strict no-touch policy for turtles. Support the local community fees which fund reef protection.'),

-- PHILIPPINES: MINDANAO
('Siargao Island Nature Spots', 'Surigao del Norte, Philippines', 'A surf and island destination with mangroves, lagoons, and nature-based tourism areas.', 4, 'Use reef-safe products, join clean-up activities, and support local conservation efforts.'),
('Davao Highlands and Parks', 'Davao Region, Philippines', 'A regional destination featuring nature parks, highland trips, and urban green spaces.', 4, 'Plan routes efficiently, minimize food waste, and follow protected area guidelines.'),
('Camiguin Island Biosphere', 'Camiguin, Philippines', 'The "Island Born of Fire," featuring volcanic springs, waterfalls, and a proactive zero-plastic local government.', 5, 'Participate in the "No Plastic" ordinance and visit the giant clam sanctuary to support local conservation.'),
('Mount Hamiguitan Range', 'Davao Oriental, Philippines', 'A UNESCO World Heritage site known for its unique pygmy forest and high level of endemic species.', 5, 'Strictly follow the "Leave No Trace" principles and register at the visitor center for guided-only treks.'),

-- ASIA & OCEANIA
('Kyoto Bamboo Groves & Temples', 'Kyoto, Japan', 'A historic city balancing mass tourism with strict preservation of shrines and natural forests.', 4, 'Use the extensive "Raku" bus network and respect "quiet zones" in residential heritage areas.'),
('Yakushima Cedar Forests', 'Kagoshima, Japan', 'A moss-covered ancient forest that inspired "Princess Mononoke," home to thousand-year-old cedar trees.', 5, 'Carry out all waste (including human waste using portable toilet kits) to protect the delicate ecosystem.'),
('Bhutan Highlands', 'Thimphu, Bhutan', 'The world’s only carbon-negative country, prioritizing "High Value, Low Impact" tourism.', 5, 'The Sustainable Development Fee (SDF) directly funds national carbon sequestration and education.'),
('Siem Reap Eco-Villages', 'Siem Reap, Cambodia', 'Beyond Angkor Wat, these villages focus on community-led tourism and handicraft preservation.', 4, 'Refill water at "Refill Not Landfill" stations and choose tuk-tuks over private luxury cars.'),
('Luang Prabang Heritage Town', 'Luang Prabang, Laos', 'A UNESCO site known for its fusion of traditional and colonial architecture and river life.', 4, 'Support the "Plastic Free Laos" initiative and participate in morning alms giving with quiet respect.'),
('Ubud Cultural Heartland', 'Bali, Indonesia', 'The center of Balinese culture, surrounded by terraced rice paddies and sustainable yoga retreats.', 4, 'Support "Zero Waste Bali" businesses and choose villas that utilize greywater recycling systems.'),
('Kerala Backwaters', 'Kerala, India', 'A network of brackish canals and lakes where traditional houseboats are transitioning to solar power.', 4, 'Opt for solar-powered houseboats and support village cooperatives that sell handmade coir products.'),
('Rotorua Geothermal Wonders', 'Bay of Plenty, New Zealand', 'A hub for Maori culture and geothermal activity with a massive focus on regenerative tourism.', 5, 'Visit the Redwoods Treewalk which uses eco-friendly suspension systems that do not harm the trees.'),
('Tasmanian Wilderness', 'Tasmania, Australia', 'Home to some of the cleanest air in the world and vast UNESCO-protected wilderness.', 4, 'Stay in off-grid eco-lodges and follow strict biosecurity protocols to protect native fauna.'),

-- EUROPE & AFRICA
('Lofoten Archipelago', 'Nordland, Norway', 'Stunning fjords and mountains focusing on "Right to Roam" responsibly and plastic-free seas.', 5, 'Utilize electric ferries and stay in "Rorbuer" (converted fisherman huts) to support local heritage.'),
('Reykjavik Geothermal Circuit', 'Reykjavik, Iceland', 'A city powered entirely by geothermal energy, serving as a gateway to glaciers and volcanoes.', 5, 'Drink tap water (it is world-class) and stick to marked trails to protect fragile volcanic moss.'),
('Azores Islands', 'Azores, Portugal', 'An Atlantic archipelago recognized for geothermal energy and sustainable whale watching.', 5, 'Look for the "Mobi-Azores" seal for eco-friendly car rentals and accommodations.'),
('Ljubljana Green Capital', 'Ljubljana, Slovenia', 'One of the first European cities to implement a "Zero Waste" strategy with a car-free center.', 5, 'Use the "Bicikelj" bike-sharing system; the entire city center is highly walkable.'),
('Swiss Alps - Zermatt', 'Valais, Switzerland', 'A world-famous mountain village that has been car-free since 1961, accessible only by train.', 5, 'Arrive via the Glacier Express; use the electric-powered local buses to navigate the village.'),
('Volcanoes National Park', 'Musanze, Rwanda', 'A global model for conservation-driven tourism, protecting the endangered mountain gorillas.', 5, 'Permit fees go directly toward local community infrastructure and anti-poaching units.'),
('Seychelles Nature Reserves', 'Mahé/Praslin, Seychelles', 'An archipelago leading the way in "Blue Economy" initiatives and marine habitat restoration.', 5, 'Support the "Nature Seychelles" programs and stay at hotels that participate in coral outplanting.'),

-- THE AMERICAS
('Monteverde Cloud Forest', 'Puntarenas, Costa Rica', 'A world-renowned pioneer in ecotourism featuring incredible biodiversity and misty canopy walks.', 5, 'The reserve is run by a non-profit; your entry fee directly funds forest expansion and protection.'),
('Galápagos Marine Reserve', 'Galápagos Islands, Ecuador', 'A living museum of evolution with strict entry requirements to protect endemic species.', 5, 'Strict "Leave No Trace" policy; all visitors must be accompanied by certified naturalist guides.'),
('Patagonia Glacial Parks', 'Magallanes, Chile', 'A rugged wilderness area emphasizing low-impact trekking and the protection of massive glaciers.', 5, 'Stick to designated trails to prevent soil erosion and use local "Refugio" huts to minimize camping impact.'),
('Tulum Eco-Chic Zone', 'Quintana Roo, Mexico', 'A coastal destination known for Mayan ruins and boutique hotels designed to coexist with the jungle.', 3, 'Seek out properties with self-contained biodigesters as the area lacks a centralized sewage system.'),
('Vancouver Urban Nature', 'British Columbia, Canada', 'A major city aiming to become the greenest in the world, surrounded by temperate rainforests.', 4, 'Utilize the "SkyTrain" and extensive bike lanes. Visit Stanley Park to see urban reforestation in action.');