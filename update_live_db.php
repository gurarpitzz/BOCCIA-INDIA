<?php
/**
 * Temporary Migration Script to Update News Articles on Production.
 * This script will run on the live server, perform the updates, and self-delete.
 */

// Allow only browser execution or secure check if needed, but since it self-deletes immediately, it is safe.
require_once __DIR__ . '/includes/db.php';

$articles = [
    1 => [
        'title' => 'Indian Boccia Team Departs for World Boccia Challenger in Bahrain',
        'excerpt' => 'Under the leadership of Jaspreet Singh Dhaliwal, the Boccia Sports Federation of India has sent a strong 7-member contingent to Manama, Bahrain.',
        'content' => '<p>The Indian Boccia team has officially departed for the prestigious World Boccia Challenger in Bahrain, scheduled from November 14 to 22, 2024. Under the leadership of Jaspreet Singh Dhaliwal, President of the Boccia Sports Federation of India (BSFI), the 7-member delegation includes top athletes such as Anjali Thakur, Pooja Gupta, Sachin, Jatin, Sarita, and Vijay.</p><p>Coaches Gurpreet Singh Dhaliwal, Jagroop Singh, Amandeep Singh, and Jaswinder Singh, along with international referee Sukhjinder Dhillon, accompany the squad. The federation\'s media in-charge Pramod Peer shared high hopes for the team\'s performance, wishing them success at the international championship which features competitors from over 20 nations.</p>',
        'meta_title' => 'Indian Boccia Team Departs for Bahrain World Challenger',
        'meta_description' => 'The Boccia Sports Federation of India has sent a 7-member contingent led by Jaspreet Singh Dhaliwal to the World Boccia Challenger in Bahrain.'
    ],
    2 => [
        'title' => '7 Indian Para-Athletes Set to Compete at World Boccia Challenger in Bahrain',
        'excerpt' => 'The 7-member Indian Boccia squad has arrived in Bahrain, ready to showcase their skills on the global stage.',
        'content' => '<p>The 7-member Indian Boccia team has arrived in Manama, Bahrain, for the World Boccia Challenger Championship, running from November 14 to November 22, 2024. Led by BSFI President Jaspreet Singh Dhaliwal, the team is fully prepared for intense matches against top global competitors.</p><p>The squad is supported by a dedicated coaching staff including Gurpreet Singh Dhaliwal, Jagroop Singh, Amandeep Singh, and Jaswinder Singh, along with international referee Sukhjinder Dhillon. The federation and supporters back home have extended their warmest wishes to the athletes as they look to bring glory to the nation.</p>',
        'meta_title' => 'Indian Para-Athletes Ready for World Boccia Challenger in Bahrain',
        'meta_description' => 'Read about the arrival and preparation of the 7-member Indian Boccia team competing at the World Boccia Challenger in Bahrain.'
    ],
    3 => [
        'title' => 'Pooja Gupta Wins India\'s First International Individual Boccia Medal',
        'excerpt' => 'Rewari\'s Pooja Gupta makes history in Bahrain by winning India\'s first individual international medal in the BC4 category.',
        'content' => '<p>Pooja Gupta has created history by becoming the first Indian athlete to claim an individual international medal in Boccia, securing a Bronze medal in the BC4 category at the World Boccia Challenger in Manama, Bahrain.</p><p>Pooja, a resident of Rewari, Haryana, who has battled hereditary motor sensory neuropathy, displayed exceptional skill and determination. Her achievement marks a major milestone for the sport in India, proving that grit and perseverance can overcome physical barriers on the world stage.</p>',
        'meta_title' => 'Historical Win: Pooja Gupta Secures India\'s First International Boccia Medal',
        'meta_description' => 'Pooja Gupta wins a historic Bronze medal at the World Boccia Challenger in Bahrain, marking a major milestone for Para Boccia in India.'
    ],
    4 => [
        'title' => 'From Rewari to Bahrain: Pooja Gupta\'s Historic Journey in Para Boccia',
        'excerpt' => 'A look at Pooja Gupta\'s inspiring journey to winning a historic Bronze medal at the international level.',
        'content' => '<p>Pooja Gupta\'s historic Bronze medal win at the World Boccia Challenger in Manama, Bahrain has brought immense pride to her hometown of Rewari. Battling hereditary motor sensory neuropathy, Pooja has consistently pushed the boundaries of what is possible in para-sports.</p><p>Supported by her family and her maternal aunt Rekha Gupta, who has been a constant source of inspiration, Pooja\'s journey highlights the power of dedicated support and tireless training. Today, she also serves as a Chief Manager at Punjab National Bank, balancing her professional career with international sporting success.</p>',
        'meta_title' => 'Inspiring Journey of Pooja Gupta to Historic International Boccia Medal',
        'meta_description' => 'Explore the inspiring story of Pooja Gupta from Rewari, who overcame physical challenges to win a historic Bronze medal in Bahrain.'
    ],
    5 => [
        'title' => 'Highlights of Pooja Gupta\'s Outstanding Achievements in Para Boccia',
        'excerpt' => 'A summary of the national and international accolades won by India\'s history-making Boccia athlete, Pooja Gupta.',
        'content' => '<p>Following her historic Bronze medal win in Bahrain, we look back at the incredible sporting career of Pooja Gupta. Her list of achievements spans multiple national championships and international representations:</p><ul><li>National Championship 2024 (Gwalior): 1 Gold, 1 Bronze</li><li>Asian Para Games 2023 (China): Represented India</li><li>World Boccia Challenger 2023 (Hong Kong): Represented India</li><li>National Championship 2023 (Delhi): 2 Gold Medals</li><li>World Boccia Challenger 2022 (Poland & Italy): Represented India</li><li>National Championship 2022 (Punjab): 2 Gold Medals</li><li>National Championship 2021 (Andhra Pradesh): 1 Bronze</li><li>National Championship 2020 (Punjab): 1 Gold Medal</li></ul><p>Her consistent performance makes her one of the most decorated Boccia athletes in India.</p>',
        'meta_title' => 'Pooja Gupta\'s Career Achievements in Para Boccia',
        'meta_description' => 'A detailed list of national and international achievements by Indian Boccia star Pooja Gupta, from 2020 to her historic medal in 2024.'
    ],
    6 => [
        'title' => 'Pooja Gupta Honored by Boccia India President for Bahrain Success',
        'excerpt' => 'President Jaspreet Singh Dhaliwal congratulates Pooja Gupta on her historic individual Bronze medal at the World Boccia Challenger.',
        'content' => '<p>Boccia Sports Federation of India (BSFI) President Jaspreet Singh Dhaliwal has officially congratulated Pooja Gupta on her historic Bronze medal win in Bahrain. Speaking on her success, the President noted that Pooja\'s victory has opened new doors for para-sports in India and will inspire many young athletes to take up Boccia.</p><p>Pooja credited her family, coaches, and the federation for their unwavering support, promising to continue training hard for upcoming international competitions, including the next Asian Para Games and World Championships.</p>',
        'meta_title' => 'BSFI President Congratulates Pooja Gupta on Historic Win',
        'meta_description' => 'Boccia India President Jaspreet Singh Dhaliwal honors Pooja Gupta for winning India\'s first individual international Boccia medal in Bahrain.'
    ],
    7 => [
        'title' => 'Indian Boccia Squad Sweeps 6 Medals at World Boccia Challenger in Bahrain',
        'excerpt' => 'An overview of the historic campaign where Indian para-athletes clinched a total of six medals in Bahrain.',
        'content' => '<p>The Indian Boccia team has concluded a sensational campaign at the World Boccia Challenger in Manama, Bahrain, clinching a total of 6 medals—including Gold, Silver, and Bronze. This marks India\'s most successful international outing in the sport of Boccia.</p><p>Leading the medal table was Sachin Chamaria, who won Gold in the BC3 Male individual category. Jatin Kumar Kushwaha and Pooja Gupta combined to win Silver in the BC4 Pairs event, while also securing individual Bronze medals in their respective categories. Ajeya Raj also won Bronze in the BC3 Male category, cementing India\'s dominance at the event.</p>',
        'meta_title' => 'India Wins 6 Medals at Bahrain World Boccia Challenger',
        'meta_description' => 'The Indian Boccia team finishes a historic campaign at the World Boccia Challenger in Bahrain, winning 6 medals including a Gold by Sachin Chamaria.'
    ],
    8 => [
        'title' => 'Sachin Chamaria Clinches Gold for India at World Boccia Challenger',
        'excerpt' => 'Sachin Chamaria delivers a masterclass performance in Bahrain to secure a historic Gold medal in the BC3 individual category.',
        'content' => '<p>India\'s Sachin Chamaria put on an outstanding display of precision and focus at the World Boccia Challenger in Bahrain, winning the Gold medal in the BC3 Male individual category. Sachin defeated top competitors to climb to the top of the podium, making it a proud moment for Indian para-sports.</p><p>BSFI officials and coaches praised Sachin\'s dedication and technical prowess during the tournament. This Gold medal represents a significant leap forward for India\'s standing in international Boccia tournaments.</p>',
        'meta_title' => 'Sachin Chamaria Wins Gold at World Boccia Challenger',
        'meta_description' => 'Sachin Chamaria wins Gold in the BC3 individual category at the World Boccia Challenger in Bahrain, establishing India\'s presence on the podium.'
    ],
    9 => [
        'title' => 'Team India Celebrates Historical 6-Medal Haul in Manama, Bahrain',
        'excerpt' => 'The Boccia Sports Federation of India celebrates the historic success of the 7-member team at the World Challenger.',
        'content' => '<p>The Boccia Sports Federation of India (BSFI) and fans across the country are celebrating the historic performance of the national team in Bahrain. With 6 medals in total (1 Gold, 1 Silver, 4 Bronze), the Indian contingent has exceeded all expectations.</p><p>The medal winners include Sachin Chamaria (Gold, BC3 Male), Jatin Kumar Kushwaha & Pooja Gupta (Silver, BC4 Pairs), Pooja Gupta (Bronze, BC4 Female), Jatin Kumar Kushwaha (Bronze, BC4 Male), and Ajeya Raj (Bronze, BC3 Male). The entire delegation, including coaches and referee Sukhjinder Dhillon, was congratulated by the federation chairman Ashok Bedi and President Jaspreet Singh Dhaliwal.</p>',
        'meta_title' => 'India Celebrates Historical 6-Medal Haul in Bahrain',
        'meta_description' => 'Details of the celebratory homecoming and congratulations for the Indian Boccia team after winning 6 medals in Manama, Bahrain.'
    ],
    10 => [
        'title' => 'Silver Glory for Jatin Kushwaha and Pooja Gupta in BC4 Pairs Event',
        'excerpt' => 'Jatin Kumar Kushwaha and Pooja Gupta display great teamwork to win Silver in the BC4 Pairs category in Bahrain.',
        'content' => '<p>The duo of Jatin Kumar Kushwaha and Pooja Gupta displayed exceptional coordination and tactical intelligence to secure the Silver medal in the BC4 Pairs category at the World Boccia Challenger in Bahrain.</p><p>Competing against highly experienced international teams, the Indian pair fought valiantly to reach the finals and bring home the Silver. Coaches and team management congratulated the duo, noting that their synergy on the court has set a new benchmark for team events in India.</p>',
        'meta_title' => 'Jatin Kushwaha and Pooja Gupta Win Silver in BC4 Pairs',
        'meta_description' => 'Indian duo Jatin Kumar Kushwaha and Pooja Gupta clinch Silver in the BC4 Pairs category at the World Boccia Challenger in Bahrain.'
    ],
    11 => [
        'title' => 'Ajeya Raj Secures Bronze Medal in BC3 Category in Bahrain',
        'excerpt' => 'Ajeya Raj adds to India\'s medal tally with a stellar Bronze medal finish in the BC3 individual category.',
        'content' => '<p>India\'s Ajeya Raj delivered a focused and composed performance at the World Boccia Challenger in Bahrain, securing a Bronze medal in the BC3 individual category. Ajeya\'s tactical play and composure under pressure earned him a spot on the international podium.</p><p>His medal added to India\'s record-breaking tally of 6 medals at the event, proving the growing depth of talent in the Indian Boccia squad across all classifications.</p>',
        'meta_title' => 'Ajeya Raj Wins Bronze at World Boccia Challenger',
        'meta_description' => 'Ajeya Raj wins a Bronze medal in the BC3 category at the World Boccia Challenger in Bahrain, adding to India\'s record-breaking medal tally.'
    ],
    12 => [
        'title' => 'Jatin Kumar Kushwaha Wins Bronze in BC4 Individual Category',
        'excerpt' => 'Jatin Kumar Kushwaha secures his second medal of the tournament with a Bronze in the BC4 individual event.',
        'content' => '<p>Jatin Kumar Kushwaha had a memorable tournament at the World Boccia Challenger in Manama, Bahrain. In addition to winning Silver in the BC4 Pairs event, Jatin clinched a Bronze medal in the BC4 Male individual category, showcasing his skills in both solo and team formats.</p><p>Jatin\'s double-medal performance has established him as one of India\'s top prospects for future international events, including the upcoming Asian Para Games.</p>',
        'meta_title' => 'Jatin Kumar Kushwaha Clinches Individual Bronze in Bahrain',
        'meta_description' => 'Jatin Kumar Kushwaha wins Bronze in the BC4 individual category, securing his second medal at the World Boccia Challenger in Bahrain.'
    ],
    13 => [
        'title' => 'Anjali Thakur and Team India Athletes Applauded for Valiant Performance',
        'excerpt' => 'Although narrowly missing out on medals, Indian athletes Anjali Thakur, Sarita, and Vijay Kumar receive high praise for their matches.',
        'content' => '<p>While India celebrated 6 medal victories at the World Boccia Challenger in Bahrain, the performances of Anjali Thakur, Sarita, and Vijay Kumar were also highly lauded. All three athletes played exceptional matches, narrowly missing out on podium finishes by small margins.</p><p>Coaches Gurpreet Singh Dhaliwal and Davinder Singh Tuffy noted that the experience gained by these athletes in competing against the world\'s best will be invaluable for their future growth and preparation.</p>',
        'meta_title' => 'Indian Athletes Applauded for Valiant Efforts in Bahrain',
        'meta_description' => 'Boccia India coaches praise the performances of Anjali Thakur, Sarita, and Vijay Kumar at the World Boccia Challenger in Bahrain.'
    ],
    14 => [
        'title' => 'Role of Support Staff and Coaches in India\'s Historic Bahrain Campaign',
        'excerpt' => 'A tribute to the dedicated coaches, physiotherapists, and officials who enabled India\'s historic 6-medal sweep in Bahrain.',
        'content' => '<p>India\'s historic success at the World Boccia Challenger in Bahrain was made possible by the dedicated support staff working behind the scenes. The coaching staff, including Gurpreet Singh Dhaliwal, Jagroop Singh, Amandeep Singh, and Jaswinder Singh, worked tirelessly to prepare the athletes.</p><p>Additionally, team physiotherapist Dr. Navjot Singh, along with Dr. Laksi and international referee Sukhjinder Dhillon, played crucial roles in ensuring the physical fitness and readiness of the players throughout the intensive competition.</p>',
        'meta_title' => 'Role of Coaches and Support Staff in Bahrain Success',
        'meta_description' => 'Learn about the contribution of coaches and medical support staff who guided the Indian Boccia team to victory in Bahrain.'
    ],
    15 => [
        'title' => 'Ashok Bedi and BSFI Officials Congratulate the National Squad',
        'excerpt' => 'BSFI Chairman Ashok Bedi and other federation leaders congratulate the players and coaching staff on their historic achievement.',
        'content' => '<p>Boccia Sports Federation of India (BSFI) Chairman Ashok Bedi, along with President Jaspreet Singh Dhaliwal and General Secretary Shaminder Singh Dhillon, has expressed immense pride in the national team\'s performance in Bahrain.</p><p>The leaders highlighted that the 6-medal haul reflects the success of the development programs and national training camps organized by the federation over the past few years, promising further investment in training facilities and sports equipment.</p>',
        'meta_title' => 'BSFI Leadership Congratulates National Boccia Squad',
        'meta_description' => 'Chairman Ashok Bedi and BSFI leaders express pride in the national team\'s historic 6-medal performance at the World Challenger.'
    ],
    16 => [
        'title' => 'Boccia India Hosts Instrument Distribution Ceremony in New Delhi',
        'excerpt' => 'BSFI organizes an equipment distribution program in Delhi to support para-athletes with high-quality sports gear.',
        'content' => '<p>The Boccia Sports Federation of India (BSFI) organized a successful sports equipment and instrument distribution program at the Ethiopian Culture Centre in Chanakyapuri, New Delhi. The initiative aims to provide para-athletes with international-standard gear to help them train and compete at the highest level.</p><p>Dignitaries, including military advisor Raja Subramani and NGT member A. Soundarrajan, attended the event. BSFI President Jaspreet Singh Dhaliwal and media in-charge Pramod Peer highlighted the federation\'s commitment to supporting grassroots development in para-sports.</p>',
        'meta_title' => 'Boccia India Equipment Distribution Ceremony in Delhi',
        'meta_description' => 'BSFI hosts a sports equipment distribution program in New Delhi to support Indian para-athletes with international-standard gear.'
    ],
    17 => [
        'title' => 'NTPC and BEML Sponsor High-Quality Equipment for Indian Boccia Players',
        'excerpt' => 'Leading public sector undertakings NTPC and BEML sponsor specialized sports equipment for India\'s para-athletes.',
        'content' => '<p>In a major boost for para-sports in India, leading organizations NTPC and BEML have sponsored high-quality, specialized playing equipment for Boccia athletes. The equipment was distributed at a special ceremony held by the BSFI in New Delhi.</p><p>Coaches Davinder Singh Tuffy and General Secretary Shaminder Singh Dhillon thanked the sponsors, stating that access to proper equipment is essential for athletes to compete effectively on the international stage.</p>',
        'meta_title' => 'NTPC and BEML Sponsor Equipment for Boccia Players',
        'meta_description' => 'Public sector undertakings NTPC and BEML step forward to sponsor specialized sports equipment for Indian Boccia para-athletes.'
    ],
    18 => [
        'title' => 'Central Bank of India Partners with BSFI for Development Initiative',
        'excerpt' => 'Central Bank of India supports the Boccia Sports Federation of India in providing resources for national athletes.',
        'content' => '<p>The Central Bank of India has partnered with the Boccia Sports Federation of India (BSFI) to support the development of para-sports. The bank provided financial and administrative backing for the recently concluded equipment distribution program in New Delhi.</p><p>BSFI leaders welcomed the partnership, emphasizing that corporate and banking sector support is vital for creating sustainable training programs and providing athletes with regular competition opportunities.</p>',
        'meta_title' => 'Central Bank of India Partners with Boccia India',
        'meta_description' => 'Central Bank of India joins hands with BSFI to support development programs and equipment distribution for para-athletes.'
    ],
    19 => [
        'title' => 'Military Advisor Raja Subramani Attends BSFI Ceremony as Chief Guest',
        'excerpt' => 'Former military chief Raja Subramani commends the dedication and spirit of India\'s Boccia athletes.',
        'content' => '<p>Former military advisor and chief Raja Subramani attended the BSFI equipment distribution program in New Delhi as the chief guest. Addressing the gathering, he commended the exceptional dedication and fighting spirit of India\'s para-athletes.</p><p>He expressed confidence that with the right support, resources, and training, Indian Boccia players will continue to shine and bring home medals from international championships.</p>',
        'meta_title' => 'Military Advisor Raja Subramani Chief Guest at BSFI Event',
        'meta_description' => 'Retired military chief Raja Subramani commends the spirit of Boccia athletes at a special distribution program in Delhi.'
    ],
    20 => [
        'title' => 'Dr. Sudhir Kumar Jain and NGT Members Support Boccia Development',
        'excerpt' => 'Prominent dignitaries and judicial members attend the BSFI Delhi event, showing solidarity with para-sports.',
        'content' => '<p>Prominent dignitaries, including Dr. Sudhir Kumar Jain and National Green Tribunal (NGT) member A. Soundarrajan, attended the BSFI development program in New Delhi to show their support for the growth of Boccia in India.</p><p>The guests interacted with international players such as Ajeya Raj, Anjali, and Pooja Gupta, expressing admiration for their achievements and promising continued support for the federation\'s initiatives.</p>',
        'meta_title' => 'Dignitaries Show Support for Boccia Development',
        'meta_description' => 'Dr. Sudhir Kumar Jain and NGT members attend the Boccia India equipment distribution program to support para-athletes.'
    ],
    21 => [
        'title' => 'International Players Ajeya Raj and Pooja Gupta Inspire Young Athletes',
        'excerpt' => 'Medal-winning athletes interact with grassroots players at the Delhi equipment distribution ceremony.',
        'content' => '<p>International medalists Ajeya Raj and Pooja Gupta were the centers of attraction at the equipment distribution ceremony in New Delhi. The seasoned players interacted with young, upcoming athletes, sharing their experiences of playing at the world level.</p><p>They emphasized the importance of regular practice, mental focus, and using the newly distributed, international-standard equipment to hone their skills.</p>',
        'meta_title' => 'Ajeya Raj and Pooja Gupta Inspire Grassroots Players',
        'meta_description' => 'Boccia stars Ajeya Raj and Pooja Gupta share training tips and inspire upcoming players at the Delhi equipment distribution event.'
    ],
    22 => [
        'title' => 'BSFI Hosts Felicitation Ceremony to Honor International Achievers',
        'excerpt' => 'Boccia Sports Federation of India holds a special ceremony to honor medal-winning athletes and coaches.',
        'content' => '<p>The Boccia Sports Federation of India (BSFI) organized a grand felicitation ceremony to celebrate and honor the achievements of the national team at recent international competitions, including the Bahrain World Challenger.</p><p>Athletes who brought home medals were presented with recognition certificates and cash awards in the presence of federation board members, coaches, and sponsors.</p>',
        'meta_title' => 'BSFI Felicitation Ceremony Honors International Achievers',
        'meta_description' => 'The Boccia Sports Federation of India hosts a special ceremony to reward and honor international medal-winning para-athletes.'
    ],
    23 => [
        'title' => 'Cash Awards Distributed to Outstanding Boccia Athletes',
        'excerpt' => 'BSFI distributes cash rewards to medal winners to support their training and preparation for future events.',
        'content' => '<p>In a bid to support the financial needs of para-athletes, the Boccia Sports Federation of India (BSFI) distributed cash rewards to outstanding players who won medals at international events.</p><p>President Jaspreet Singh Dhaliwal noted that these rewards are meant to help athletes cover their training expenses and purchase specialized equipment, ensuring they can focus entirely on preparation.</p>',
        'meta_title' => 'Cash Rewards for Medal-Winning Boccia Athletes',
        'meta_description' => 'BSFI distributes cash rewards and grants to international medalists to support their ongoing training and preparation.'
    ],
    24 => [
        'title' => 'Recognition Certificates Presented to Support Staff and Coaches',
        'excerpt' => 'Coaches and support staff are recognized for their invaluable contribution to the team\'s international success.',
        'content' => '<p>During the BSFI felicitation ceremony, recognition certificates were presented to coaches and medical support staff to honor their invaluable contribution to the national team\'s success.</p><p>Coaches Gurpreet Singh Dhaliwal, Amandeep Singh, and referee Sukhjinder Dhillon were among those recognized for their tireless dedication and support behind the scenes.</p>',
        'meta_title' => 'Coaches and Support Staff Recognized by BSFI',
        'meta_description' => 'BSFI presents certificates of appreciation to coaches and support staff for their guidance of the national squad.'
    ],
    25 => [
        'title' => 'Young Para-Athletes Motivated by Federation Support and Grants',
        'excerpt' => 'The distribution of cash grants and equipment motivates young players to excel at upcoming national championships.',
        'content' => '<p>The recent distribution of sports equipment and cash grants by the BSFI has highly motivated young para-athletes across various states. Many upcoming players expressed gratitude to the federation for providing them with the resources needed to excel.</p><p>The federation aims to identify and nurture talent at the grassroots level, preparing them for future selection trials and national games.</p>',
        'meta_title' => 'Young Athletes Motivated by BSFI Grants and Support',
        'meta_description' => 'Upcoming para-athletes express gratitude to the BSFI for equipment support and cash grants helping them prepare for nationals.'
    ],
    26 => [
        'title' => 'Boccia India Announces Expansion of Grassroots Training Programs',
        'excerpt' => 'Following a successful year, BSFI plans to launch new coaching camps and development initiatives across India.',
        'content' => '<p>Following the outstanding success of the national team in Bahrain and Delhi, the Boccia Sports Federation of India (BSFI) has announced the expansion of its grassroots training programs.</p><p>New coaching camps and equipment distribution centers will be established in various states, including Punjab, Haryana, Himachal Pradesh, and Delhi, to make the sport accessible to more disabled athletes and find the next generation of international champions.</p>',
        'meta_title' => 'Boccia India Launches Grassroots Training Programs',
        'meta_description' => 'BSFI announces new training camps and coaching programs across India to discover and nurture young para-athletes.'
    ]
];

echo "<h2>Starting Live Database Update...</h2>";
$count = 0;
$stmt = $pdo->prepare("UPDATE news SET title = ?, excerpt = ?, content = ?, meta_title = ?, meta_description = ?, status = 'published' WHERE id = ?");

foreach ($articles as $id => $data) {
    if ($stmt->execute([
        $data['title'],
        $data['excerpt'],
        $data['content'],
        $data['meta_title'],
        $data['meta_description'],
        $id
    ])) {
        echo "Updated article ID: $id<br>";
        $count++;
    }
}

echo "<h3>Successfully updated $count news articles!</h3>";

// Secure self-deletion
if (unlink(__FILE__)) {
    echo "<h3>Security Cleanup: This script has successfully self-deleted from the server.</h3>";
} else {
    echo "<h3>Warning: Could not self-delete the script. Please delete it manually for security.</h3>";
}
?>
