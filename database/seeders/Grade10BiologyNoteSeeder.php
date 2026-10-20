<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Grade;
use App\Models\Note;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Grade10BiologyNoteSeeder extends Seeder
{
    /**
     * Run the database seeds for Grade 10 Biology Notes.
     */
    public function run(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            config(['database.default' => 'sqlite']);
        }

        DB::transaction(function () {
            // 1. Find or create Grade 10
            $grade = Grade::where('name', 'Grade 10')->first();
            if (!$grade) {
                $grade = Grade::create([
                    'name' => 'Grade 10',
                    'type' => 1,
                ]);
            }

            // 2. Find or create Subject "Biology" for Grade 10
            $subject = Subject::where('grade_id', $grade->id)
                ->where('title', 'Biology')
                ->first();

            if (!$subject) {
                $subject = Subject::create([
                    'grade_id' => $grade->id,
                    'title' => 'Biology',
                    'description' => 'Grade 10 Biology Curriculum.',
                ]);
            }

            // 3. Define Grade 10 Biology Notes Data (converted from LaTeX to human-readable formulas)
            $notesData = [
                1 => [
                    'chapter_title' => 'Sub-fields of Biology',
                    'note_title'    => 'Unit 1: Sub-fields of Biology Notes',
                    'content'       => <<<'MARKDOWN'
Biology explores living organisms, their internal processes, interactions, and environmental relationships. Modern biology encompasses major branches, specialized sub-disciplines, foundational scientific relationships, and key historical discoveries.

#### Major Divisions and Specialized Sub-fields

**Core Branches**

* **Zoology:** The scientific study of animals, encompassing sub-disciplines such as herpetology (reptiles), ichthyology (fish), mammalogy (mammals), ornithology (birds), and entomology (insects).
* **Botany:** The study of plant structure, physiological properties, biochemical processes, classification, disease management, and environmental interactions.
* **Microbiology:** The study of microscopic organisms, including bacteriology (bacteria) and mycology (fungi), as well as archaea, protists, microscopic algae, and viruses.

**Structural and Functional Disciplines**

* **Morphology:** Analysis of external forms and structural features.
* **Anatomy:** Study of internal bodily structures revealed via dissection.
* **Histology:** Microscopic examination of biological tissues.
* **Cytology and Cell Biology:** Investigation of cellular structures, organelles, and functions.
* **Molecular Biology:** Study of informational molecules, including DNA and RNA.
* **Physiology:** Scientific study of body function and vital processes.
* **Taxonomy:** Classification, naming, and identification of organisms.
* **Genetics:** Study of heredity and inherited variation.

#### Pure vs. Applied Fields

* **Pure Biology:** Theoretical exploration of natural life mechanisms, including behavior, structure, reproduction, and evolutionary history. Examples include taxonomy, cytology, histology, embryology, and physiology.
* **Applied Biology:** Practical application of biological principles to manage and improve life processes. Applied fields include agriculture, horticulture, forestry, biotechnology, animal husbandry, pharmacognosy, and pathology.

#### Interdisciplinary Applied Sciences

* **Biotechnology:** Technical manipulation of living organisms or genetic material for human benefit.
* **Bioinformatics:** Acquisition, storage, analysis, and dissemination of biological sequence data (such as DNA and amino acids) using computational tools.
* **Genetic Engineering:** Direct extraction, synthesis, and insertion of genes into host organisms.
* **Biomedical Engineering:** Application of engineering tools to advance human health and medical systems.
* **Forensic Science:** Application of biological tools (e.g., DNA profiling, blood typing) to civil and criminal legal proceedings.

#### Historical Breakthroughs and Pioneers

* **Aristotle (384–322 BC):** Devised early classification systems grouping organisms by structural features and movement capability.
* **Galen (129–161 AD):** Advanced anatomical understanding, distinguishing between veins and arteries and identifying the larynx's role in vocalization.
* **Antonie van Leeuwenhoek (1632–1723):** Refined high-power microscopy lenses, enabling the discovery of bacteria, single-celled organisms, and cellular structures.
* **Carl Linnaeus (1707–1775):** Formulated modern binomial nomenclature and hierarchical classification (Kingdom down to Species).
* **Charles Darwin (1809–1882):** Formulated the theory of evolution through natural selection, published in *On the Origin of Species* (1859).
* **Gregor Mendel (1822–1884):** Formulated fundamental principles of genetic inheritance (dominant and recessive traits) through pea plant experiments.
* **Louis Pasteur (1822–1895):** Established the Germ Theory of Disease, pioneered pasteurization, and produced vaccines for anthrax and rabies.
* **Robert Koch (1843–1910):** Identified specific causative agents for tuberculosis, cholera, and anthrax, establishing modern medical bacteriology.

#### Notable Ethiopian Biologists

* **Dr. Aklilu Lemma:** Discovered that the soapberry plant (*Endod*, *Phytolacca dodecandra*) acts as a low-cost, eco-friendly molluscicide to kill freshwater snails that transmit Schistosomiasis (Bilharzia).
* **Prof. Gebissa Ejeta:** Developed drought-tolerant and Striga-weed-resistant commercial hybrid varieties of sorghum, earning the World Food Prize (2009).
* **Prof. Yalemtsehay Mekonnen:** Specialized in human physiology and researched the health impacts of chemical pesticide hazards.
MARKDOWN
                ],

                2 => [
                    'chapter_title' => 'Plants',
                    'note_title'    => 'Unit 2: Plants Notes',
                    'content'       => <<<'MARKDOWN'
Plants are multicellular, autotrophic, non-motile (sessile) eukaryotic organisms that undergo both asexual and sexual reproduction.

#### Major Plant Classification

* **Gymnosperms (Non-Flowering Seed Plants):** Vascular plants whose seeds develop exposed on the scales of reproductive cones rather than enclosed in fruits. Examples include *Pinus* (pine), *Podocarpus falcatus* (*Zigba*), and *Juniperus procera* (*Tid*).
* **Angiosperms (Flowering Seed Plants):** Vascular plants producing flowers and protective fruits that enclose seeds. Angiosperms split into two major classes:
  * **Monocots:** Seeds contain a single cotyledon, leaves have parallel veins, stem vascular bundles are scattered, and roots are fibrous.
  * **Dicots:** Seeds contain two cotyledons, leaves exhibit reticulate (netted) venation, stem vascular bundles are arranged in a ring, and root systems feature a main taproot.

#### External Plant Anatomy

* **Shoot System:** Above-ground vegetative and reproductive structures.
  * **Stem:** Provides structural support, positions leaves toward light, and transports internal materials via vascular bundles.
  * **Leaf:** The main organ for light absorption, gas exchange, and photosynthesis. Consists of a flat blade (lamina), petiole (stalk), midrib, and veins.
* **Root System:** Below-ground anchored structures.
  * **Taproot:** A dominant central primary root with minor lateral branches, anchoring deep into soil.
  * **Fibrous Root:** A dense network of slender, superficial roots that bind topsoil and absorb shallow soil moisture.
  * **Root Hairs:** Fine, single-celled extensions of the root surface layer (piliferous layer) that increase surface area for water and mineral absorption.

#### Internal Structure of Plant Organs

* **Leaf Micro-Anatomy:**
  * **Epidermis:** Upper and lower protective outer cell layers covered by a waxy cuticle. Lower epidermis contains specialized guard cells surrounding stomatal pores that regulate water vapor loss and gas exchange (CO₂ and O₂).
  * **Mesophyll Layer:** Photosynthetic middle region divided into tightly packed, columnar **palisade cells** (rich in chloroplasts for maximum light capture) and loosely arranged **spongy cells** (facilitating gas movement).
* **Stem Micro-Anatomy:**
  * **Outer Cortex and Endodermis:** Provide structural protection, mechanical support, and nutrient storage.
  * **Vascular Ring/Bundles:** Composed of outer phloem, active meristematic cambium (allowing secondary growth in dicots), and inner xylem. Central core is called the pith.
* **Root Micro-Anatomy:** Cross-section reveals the outer piliferous layer, broad storage cortex, regulating endodermis, pericycle (site of lateral root origin), and central vascular tissues.

#### Floral Structure and Angiosperm Reproduction

* **Floral Whorls:**
  * **Calix (Sepals):** Protective outer green structures.
  * **Corolla (Petals):** Brightly colored structures that attract pollinators.
  * **Androecium (Stamen):** Male reproductive organ, comprising anther (pollen producer) and supporting filament.
  * **Gynoecium/Carpel (Pistil):** Female reproductive organ, comprising stigma (pollen receptor), style, and ovary containing ovules.
* **Pollination and Seed Formation:** Pollen transfer occurs via wind, water, or animal vectors. Fertilization of the egg within the ovule produces an embryo. The fertilized ovule matures into a seed, while the surrounding ovary develops into a fruit.
* **Seed Germination:** Emergence of the plant embryo driven by moisture, oxygen, and warmth.
  * **Epigeal Germination:** The hypocotyl elongates, pushing cotyledons above soil level.
  * **Hypogeal Germination:** The epicotyl elongates, keeping cotyledons below ground level.

#### Photosynthesis and Plant Physiology

* **Photosynthesis Reaction:**

6CO₂ + 6H₂O --(Sunlight / Chlorophyll)--> C₆H₁₂O₆ + 6O₂

* **Light-Dependent Stage:** Occurs in chloroplast thylakoids/grana. Absorbed solar energy splits water molecules (photolysis), producing O₂ alongside high-energy ATP and NADPH molecules.
* **Dark Stage (Light-Independent / Calvin Cycle):** Occurs in the chloroplast stroma. Enzymatic reactions use stored ATP and H⁺ to fix atmospheric CO₂ into carbohydrates (glucose).
* **Vascular Transport Systems:**
  * **Xylem:** Transports water and inorganic minerals upward from roots to shoots driven by root pressure, capillary action, and evaporation-driven transpiration pull.
  * **Phloem:** Translocates organic food products (sucrose) actively from synthesis sites (sources/leaves) to storage/growth sites (sinks).

#### Plant Responses and Medicinal Importance

* **Tropisms:** Growth movements in response to unidirectional environmental stimuli.
  * **Phototropism:** Directional growth influenced by light, mediated by asymmetric distribution of the hormone auxin (shoots grow toward light, showing positive phototropism).
  * **Geotropism/Gravitropism:** Growth response to gravity (roots display positive geotropism; shoots display negative geotropism).
  * **Hydrotropism:** Root growth directed toward soil moisture.
* **Ethnobotany and Traditional Medicine:** Indigenous plants serve as primary healthcare treatments. Key Ethiopian examples include:
  * *Hagenia abyssinica* (*Kosso*): Female flowers used orally to treat tapeworm infections.
  * *Zingiber officinale* (*Jinjibil*): Rhizome used for coughs, tonsillitis, and abdominal discomfort.
  * *Ruta chalepensis* (*Tena Adam*): Fresh leaves used for stomach conditions.
  * *Ocimum lamifolium* (*Damakesse*): Leaves crushed for nasal inhalation to relieve headaches and fever.
MARKDOWN
                ],

                3 => [
                    'chapter_title' => 'Human Biology',
                    'note_title'    => 'Unit 3: Human Biology Notes',
                    'content'       => <<<'MARKDOWN'
Human biology explores the structure, function, health, and physiological mechanisms of the human body, focusing on key systems, nutritional requirements, and common diseases.

#### Human Body Systems

* **Digestive System:** Breaks down food mechanically and chemically to absorb nutrients and eliminate solid waste.
  * *Key Organs:* Mouth, esophagus, stomach, small intestine, large intestine, liver, and pancreas.
  * *Enzymes:* Salivary amylase (carbohydrates), pepsin (proteins in stomach), lipase (fats), trypsin (proteins in small intestine).
* **Circulatory System:** Transports oxygen, nutrients, hormones, and cellular waste throughout the body.
  * *Components:* Heart (4-chambered pump), blood vessels (arteries, veins, capillaries), and blood (plasma, red blood cells, white blood cells, platelets).
  * *Circulation Pathways:* Pulmonary circulation (heart to lungs for gas exchange) and systemic circulation (heart to the rest of the body).
* **Respiratory System:** Facilitates external gas exchange by taking in oxygen and exhaling carbon dioxide.
  * *Key Organs:* Nasal cavity, pharynx, larynx, trachea, bronchi, bronchioles, and alveoli (site of gas exchange).
  * *Mechanism:* Driven by the contraction and relaxation of the diaphragm and intercostal muscles.
* **Excretory System:** Removes metabolic wastes, excess water, and salts to maintain internal osmotic balance (homeostasis).
  * *Key Organs:* Kidneys (filtering unit: nephron), ureters, urinary bladder, urethra, and skin (sweat glands).
* **Nervous System:** Controls and coordinates body functions by transmitting electrical and chemical signals.
  * *Central Nervous System (CNS):* Brain and spinal cord.
  * *Peripheral Nervous System (PNS):* Somatic (voluntary) and autonomic (involuntary: sympathetic and parasympathetic) systems.
* **Endocrine System:** Regulates long-term physiological processes (growth, metabolism, reproduction) via chemical messengers called hormones.
  * *Key Glands:* Pituitary (master gland), thyroid, adrenal glands, pancreas (insulin and glucagon), and gonads (testes and ovaries).

#### Human Nutrition and Balance

* **Macronutrients:**
  * *Carbohydrates:* Primary source of cellular energy (e.g., glucose, starch).
  * *Proteins:* Essential for tissue repair, growth, and enzyme production (composed of amino acids).
  * *Lipids (Fats):* Long-term energy storage, cell membrane structure, and organ insulation.
* **Micronutrients:**
  * *Vitamins:* Organic compounds required in small amounts (e.g., Vitamin A for vision, Vitamin C for immunity, Vitamin D for bone health).
  * *Minerals:* Inorganic elements essential for structural and regulatory functions (e.g., Iron for hemoglobin, Calcium for bones and muscle contraction).
* **Balanced Diet:** Contains all essential nutrients in correct proportions alongside dietary fiber and adequate water to support healthy metabolism.

#### Health, Diseases, and Immunity

* **Infectious Diseases:** Pathogen-driven conditions transmitted directly or indirectly between hosts.
  * *Bacterial:* Tuberculosis, cholera, typhoid fever.
  * *Viral:* HIV/AIDS, influenza, measles, COVID-19.
  * *Protozoan:* Malaria (transmitted by female *Anopheles* mosquitoes).
* **Non-Communicable Diseases:** Non-infectious health conditions influenced by genetics, environment, or lifestyle choices (e.g., diabetes, cardiovascular diseases, hypertension, cancer).
* **Immune System:** The body's defense framework against foreign invaders.
  * *Non-Specific (Innate) Immunity:* Physical and chemical barriers like skin, mucous membranes, stomach acid, and phagocytic white blood cells.
  * *Specific (Adaptive) Immunity:* Target-specific responses involving B-lymphocytes (produce antibodies) and T-lymphocytes (destroy infected cells).
MARKDOWN
                ],

                4 => [
                    'chapter_title' => 'Ecology',
                    'note_title'    => 'Unit 4: Ecology Notes',
                    'content'       => <<<'MARKDOWN'
Ecology is the study of interactions between living organisms (biotic factors) and their non-living physical environment (abiotic factors).

#### Levels of Ecological Organization

* **Organism:** An individual living entity.
* **Population:** A group of individuals of the same species living in a specific area at a given time.
* **Community:** All the interacting populations of different species inhabiting a shared area.
* **Ecosystem:** A biological community interacting with its non-living physical environment.
* **Biome:** A large geographic region characterized by distinct climate patterns, vegetation, and animal communities (e.g., tropical rainforest, savannah, desert).
* **Biosphere:** The global sum of all ecosystems supporting life on Earth.

#### Ecosystem Dynamics and Energy Flow

* **Biotic and Abiotic Factors:**
  * *Biotic:* Producers (autotrophs), consumers (heterotrophs), and decomposers (saprotrophs like fungi and bacteria).
  * *Abiotic:* Sunlight, temperature, water, soil composition, wind, and pH levels.
* **Trophic Levels and Food Chains:**
  * **Producers (1st Trophic Level):** Green plants and algae that capture solar energy via photosynthesis.
  * **Primary Consumers (2nd Level):** Herbivores that feed on producers.
  * **Secondary/Tertiary Consumers (3rd/4th Level):** Carnivores and omnivores that prey on other animals.
  * **Decomposers:** Breakdown organic waste and dead organisms, recycling nutrients back into the soil.
* **Energy Flow Rules:** Energy enters ecosystems as sunlight and flows linearly through trophic levels. Approximately **10%** of energy is transferred from one level to the next, while 90% is lost as metabolic heat.

#### Biogeochemical Cycles

* **Carbon Cycle:** Movement of carbon between the atmosphere, living organisms, soil, and oceans through photosynthesis, cellular respiration, combustion of fossil fuels, and decomposition.
* **Nitrogen Cycle:** Converts inert atmospheric nitrogen (N₂) into usable forms for living organisms.
  * *Nitrogen Fixation:* Bacteria (e.g., *Rhizobium* in legume root nodules) convert N₂ into ammonia (NH₃).
  * *Nitrification:* Nitrifying bacteria convert ammonia into nitrites (NO₂⁻) and then nitrates (NO₃⁻).
  * *Assimilation:* Plants absorb nitrates to build proteins and nucleic acids.
  * *Denitrification:* Denitrifying bacteria convert soil nitrates back into atmospheric N₂ gas.
* **Water (Hydrological) Cycle:** Continuous movement of water via evaporation, transpiration (from plants), condensation, precipitation, and percolation/runoff.

#### Ecosystem Interactions and Biodiversity Conservation

* **Interspecific Relationships:**
  * **Predation:** One organism (predator) hunts and kills another (prey).
  * **Competition:** Organisms compete for limited resources (food, space, mates).
  * **Symbiosis:**
    * *Mutualism:* Both species benefit (e.g., bees and flowering plants).
    * *Commensalism:* One species benefits; the other is unaffected (e.g., epiphytes on trees).
    * *Parasitism:* One organism benefits (parasite) at the expense of the host (e.g., tapeworms in human intestines).
* **Conservation and Human Impact:**
  * *Threats:* Deforestation, habitat fragmentation, pollution, climate change, overexploitation, and invasive species.
  * *Conservation Strategies:* Establishing protected areas (national parks, sanctuaries), reforestation, sustainable resource management, and protecting endangered species.
MARKDOWN
                ],

                5 => [
                    'chapter_title' => 'Health and Disease',
                    'note_title'    => 'Unit 5: Health and Disease Notes',
                    'content'       => <<<'MARKDOWN'
Health encompasses physical, mental, and social well-being, while disease represents any impairment of normal physiological or biological body functions.

#### Categorization of Diseases

* **Communicable (Infectious) Diseases:** Diseases caused by biological pathogens that spread directly or indirectly from one host to another.
  * *Bacterial:* Tuberculosis (*Mycobacterium tuberculosis*), cholera (*Vibrio cholerae*), typhoid fever (*Salmonella typhi*).
  * *Viral:* HIV/AIDS, influenza, measles, hepatitis, viral gastroenteritis.
  * *Protozoan:* Malaria (*Plasmodium* species), amoebic dysentery (*Entamoeba histolytica*), leishmaniasis.
  * *Fungal:* Ringworm, athlete's foot, candidiasis.
  * *Helminthic (Parasitic Worms):* Ascariasis (roundworm), schistosomiasis (flukeworm), taeniasis (tapeworm).
* **Non-Communicable Diseases (NCDs):** Chronic conditions that are non-transmissible and typically caused by genetic, physiological, environmental, or lifestyle factors.
  * *Cardiovascular Diseases:* Hypertension, coronary artery disease, stroke.
  * *Metabolic Disorders:* Type 1 and Type 2 diabetes mellitus.
  * *Neoplastic Disorders:* Various types of cancer characterized by uncontrolled cell division.
  * *Nutritional Deficiencies:* Marasmus (energy deficiency), kwashiorkor (protein deficiency), anemia (iron deficiency), goiter (iodine deficiency).

#### Disease Transmission Pathways

* **Direct Contact:** Person-to-person physical transmission via touch, kissing, or sexual contact.
* **Airborne Transmission:** Droplet nuclei or dust particles inhaled through coughing, sneezing, or breathing.
* **Vector-Borne Transmission:** Transfer of pathogens via living organisms (vectors), such as *Anopheles* mosquitoes carrying malaria or tsetse flies carrying sleeping sickness.
* **Vehicle-Borne Transmission:** Ingestion of contaminated food, drinking water, or exposure to contaminated inanimate objects (fomites).

#### Immunity and Defense Mechanisms

* **Innate (Non-Specific) Immunity:** First and second lines of defense active against a broad range of pathogens.
  * *Physical/Chemical Barriers:* Intact skin, mucous membranes, cilia, stomach acid (HCl), and lysozymes in tears/saliva.
  * *Cellular/Internal Responses:* Inflammation, fever, phagocytic white blood cells (macrophages, neutrophils).
* **Adaptive (Specific) Immunity:** Target-specific defense characterized by immunological memory.
  * *Humoral Immunity:* B-lymphocytes detect antigens and produce specific proteins called antibodies.
  * *Cell-Mediated Immunity:* T-lymphocytes (cytotoxic T-cells) identify and destroy infected host cells directly.
  * *Acquired Immunity Types:*
    * *Active Natural:* Infection recovery leading to long-term memory cells.
    * *Active Artificial:* Vaccination using weakened, killed, or fragment pathogens to stimulate antibody production.
    * *Passive Natural:* Maternal antibody transfer across the placenta or through breast milk.
    * *Passive Artificial:* Direct injection of pre-formed antibodies (serum).

#### Public Health and Hygiene

* **Personal Hygiene:** Handwashing with soap, dental hygiene, safe food preparation, and proper sanitation practices.
* **Environmental Health:** Safe sewage disposal, access to clean drinking water, vector control (draining stagnant water, using insecticide-treated bed nets), and solid waste management.
* **Substance Abuse:** Health impact of alcohol, tobacco, and illegal drugs on body organs (e.g., liver cirrhosis, lung cancer, addiction, cardiovascular damage).
MARKDOWN
                ],

                6 => [
                    'chapter_title' => 'Ecology and Environment',
                    'note_title'    => 'Unit 6: Ecology and Environment Notes',
                    'content'       => <<<'MARKDOWN'
Ecology focuses on environmental interactions, biodiversity preservation, and managing natural resources alongside human-induced environmental impacts.

#### Ecosystem Dynamics and Structure

* **Ecosystem Components:**
  * *Biotic:* Producers (autotrophs), consumers (primary, secondary, tertiary heterotrophs), and decomposers/detritivores.
  * *Abiotic:* Solar radiation, temperature regimes, rainfall, soil properties (pH, texture, nutrients), and altitude.
* **Ecological Niche vs. Habitat:**
  * *Habitat:* The physical location or environment where an organism lives.
  * *Niche:* The functional role and position of a species within its ecosystem, including resource use and interactions.
* **Ecological Pyramids:** Graphical representations of trophic levels within an ecosystem.
  * *Pyramid of Numbers:* Represents individual counts at each trophic level.
  * *Pyramid of Biomass:* Represents total living organic matter present at each level.
  * *Pyramid of Energy:* Represents energy flow over time; always upright due to the **10% energy transfer rule** (90% lost as heat).

#### Ecological Succession

* **Primary Succession:** Establishment of biological communities in previously uninhabited areas lacking soil (e.g., bare rock, cooled lava flows). Pioneered by lichens and mosses.
* **Secondary Succession:** Rebuilding of biological communities in areas where existing vegetation was destroyed but soil remains intact (e.g., abandoned farmland, burnt forests).
* **Climax Community:** A stable, mature biological community reaching equilibrium with local climate conditions.

#### Human Environmental Impacts

* **Deforestation:** Removal of forest cover leading to soil erosion, loss of biodiversity, disrupted water cycles, and increased atmospheric CO₂.
* **Pollution:**
  * *Air Pollution:* Industrial emissions, vehicle exhaust, greenhouse gases (CO₂, CH₄), causing smog, acid rain, and global warming.
  * *Water Pollution:* Industrial effluents, agricultural runoff (pesticides, fertilizers causing **eutrophication**), and untreated sewage.
  * *Soil Pollution:* Improper hazardous waste disposal, heavy metals, and non-biodegradable plastics.
* **Climate Change:** Long-term global shifts in temperature and weather patterns driven primarily by human greenhouse gas emissions.
* **Loss of Biodiversity:** Extinction of species driven by habitat destruction, overexploitation, invasive species introduction, and global pollution.

#### Environmental Conservation and Management

* **In-situ Conservation:** Protection of ecosystems and species within their natural habitats (e.g., national parks, nature reserves, biosphere reserves).
* **Ex-situ Conservation:** Protection of endangered biological components outside their natural habitats (e.g., botanical gardens, zoos, seed banks, gene banks).
* **Sustainable Resource Management:**
  * *Reforestation and Afforestation:* Planting trees to restore cleared land or create new forests.
  * *Soil Conservation:* Contour plowing, terracing, crop rotation, windbreaks, and cover cropping to minimize topsoil loss.
  * *Waste Management:* Implementing the **3 Rs** (Reduce, Reuse, Recycle) alongside composting and safe waste disposal methods.
MARKDOWN
                ],
            ];

            foreach ($notesData as $chapterNumber => $data) {
                // Ensure Chapter exists with matching chapter_number and chapter_title
                $chapter = Chapter::updateOrCreate(
                    [
                        'subject_id'     => $subject->id,
                        'chapter_number' => $chapterNumber,
                    ],
                    [
                        'grade_id'    => $grade->id,
                        'title'       => $data['chapter_title'],
                        'description' => "Chapter {$chapterNumber}: {$data['chapter_title']}",
                    ]
                );

                // Create or update the Note record for this chapter
                Note::updateOrCreate(
                    [
                        'chapter_id' => $chapter->id,
                    ],
                    [
                        'grade_id'   => $grade->id,
                        'subject_id' => $subject->id,
                        'title'      => $data['note_title'],
                        'content'    => $data['content'],
                    ]
                );
            }
        });

        Cache::flush();
    }
}
