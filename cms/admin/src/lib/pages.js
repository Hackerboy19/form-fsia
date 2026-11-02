// The five pages the CMS manages, and the editable sections of the Home and
// About pages. Each section is stored as one page_sections row: `fields` become
// keys of its JSON `content`, and `image` adds an image upload (image_url).
// To add a field or a section, edit this file: no database change is needed.

export const PAGES = [
  { slug: 'index', label: 'Home', file: 'index.php' },
  { slug: 'about', label: 'About', file: 'about.php', preview: '/about.php' },
  { slug: 'our-teams', label: 'Our Team', file: 'our-teams.php', preview: '/our-teams.php' },
  { slug: 'news-coverage', label: 'News Coverage', file: 'news-coverage.php' },
  { slug: 'special-news-coverage', label: 'Special Coverage', file: 'special-news-coverage.php' },
];

// Icons for the About page cards.
const ICONS = [{ value: '💪', label: '💪 Strength' }, { value: '🏆', label: '🏆 Trophy' }, { value: '🌟', label: '🌟 Star' }, { value: '⭐', label: '⭐ Star 2' }, { value: '👑', label: '👑 Crown' }, { value: '🎯', label: '🎯 Target' }, { value: '🚀', label: '🚀 Rocket' }, { value: '💡', label: '💡 Idea' }, { value: '🤝', label: '🤝 Handshake' }, { value: '❤️', label: '❤️ Heart' }, { value: '🎖️', label: '🎖️ Medal' }, { value: '🌍', label: '🌍 World' }, { value: '🇮🇳', label: '🇮🇳 India' }, { value: '✨', label: '✨ Sparkle' }, { value: '📣', label: '📣 Announce' }, { value: '🎓', label: '🎓 Education' }];

export const SECTION_SCHEMAS = {
  index: [
    {
      key: 'hero',
      title: 'Hero banner',
      image: { label: 'Hero image', size: { w: 1600, h: 900, fit: 'cover' } },
      fields: [
        { name: 'eyebrow', label: 'Small label above the title', max: 80, placeholder: 'Beauty Pageants • National Awards • Fashion' },
        { name: 'title', label: 'Headline', max: 140, required: true, placeholder: 'Real People. Real Stories. A Brighter India.' },
        { name: 'subtitle', label: 'Intro paragraph', type: 'textarea', max: 600 },
        { name: 'cta_label', label: 'Button text', max: 40, placeholder: 'Quick Apply • 2026 Auditions' },
        { name: 'cta_url', label: 'Button link', type: 'url', max: 300, placeholder: 'https://www.fsia.in/quickapply' },
      ],
    },
    {
      key: 'about_intro',
      title: 'About block',
      image: { label: 'Side image', size: { w: 1200, h: 900, fit: 'cover' } },
      fields: [
        { name: 'heading', label: 'Heading', max: 120, required: true },
        { name: 'body', label: 'Text', type: 'textarea', max: 2000 },
      ],
    },
    {
      key: 'final_cta',
      title: 'Closing call to action',
      fields: [
        { name: 'badge', label: 'Badge', max: 80, placeholder: 'Season 2026 Auditions & Nominations Active' },
        { name: 'heading', label: 'Heading', max: 120, placeholder: 'Step onto the National Stage' },
        { name: 'body', label: 'Text', type: 'textarea', max: 600 },
        { name: 'primary_label', label: 'Main button text', max: 40 },
        { name: 'primary_url', label: 'Main button link', type: 'url', max: 300 },
      ],
    },
  ],
  // about.php: every text on the page, top to bottom. Empty box = the page's
  // built-in text (shown grey). **word** = bold; blank line = new paragraph.
  about: [
    {
      key: 'hero',
      title: '1. Page header',
      fields: [
        { name: 'eyebrow', label: 'Badge label', max: 80, def: 'About' },
        { name: 'title', label: 'Main title', max: 140, def: 'Forever Star India' },
        { name: 'subtitle', label: 'Line under the title', max: 200, def: 'Beauty Pageants & Awards' },
      ],
    },
    {
      key: 'intro',
      title: '2. About Forever Star India',
      fields: [
        { name: 'heading', label: 'Heading', max: 120, def: 'About Forever Star India' },
        { name: 'body', label: 'Text', type: 'textarea', max: 4000, rows: 10, hint: 'Blank line = new paragraph. **word** = bold.',
          def: "**Forever Star India** is a National and International Brand represented by its Star Logo, a trademark registered under **Class 41** with the Government of India. The brand brings together individuals from different backgrounds through beauty pageants, international pageants, sports, education, kids' competitions, awards, and various talent recognition initiatives.\n\nForever Star India was founded with the belief that talent should never remain unnoticed because of a lack of opportunity. India has talent in every city, every town, and every community. The responsibility of the brand is to create platforms where deserving individuals can present their abilities, earn recognition on merit, and move forward with confidence.\n\nEvery initiative undertaken by Forever Star India is guided by this purpose." },
      ],
    },
    {
      key: 'story',
      title: '3. The Purpose Behind Forever Star India',
      fields: [
        { name: 'heading', label: 'Heading', max: 120, def: 'The Purpose Behind Forever Star India' },
        { name: 'lead', label: 'Bold first line', type: 'textarea', rows: 2, max: 400, def: 'The purpose of Forever Star India is to ensure that talent receives an opportunity before it is judged.' },
        { name: 'text', label: 'Paragraph', type: 'textarea', rows: 6, max: 3000, hint: '**word** = bold.',
          def: 'The brand believes that recognition is not reserved for a select few. Every individual deserves a fair platform to participate, perform, and be recognised for dedication, ability, and effort. By creating opportunities across multiple categories, Forever Star India encourages individuals to discover their potential, strengthen their confidence, and contribute through their talent for their own growth as well as for society and the nation.' },
      ],
    },
    {
      key: 'mission',
      title: '4. Mission, Goals & Vision cards',
      fields: [
        { name: 'mission_icon', label: 'Card 1 icon', type: 'select', def: '💪', options: ICONS },
        { name: 'mission_title', label: 'Card 1 title', max: 60, def: 'Our Mission' },
        { name: 'mission', label: 'Card 1 points (one per line)', type: 'textarea', max: 1500, rows: 6,
          def: "To provide fair and accessible opportunities for individuals across India.\nTo identify and recognize talent through transparent and merit-based platforms.\nTo encourage people to participate with confidence and determination.\nTo promote talent across beauty pageants, sports, education, awards, kids' competitions, and other recognition initiatives.\nTo uphold values of merit, discipline, integrity, and equal opportunity." },
        { name: 'goals_icon', label: 'Card 2 icon', type: 'select', def: '🏆', options: ICONS },
        { name: 'goals_title', label: 'Card 2 title', max: 60, def: 'Our Goals' },
        { name: 'goals', label: 'Card 2 points (one per line)', type: 'textarea', max: 1500, rows: 6,
          def: "To reach talented individuals from every state, district, and community of India.\nTo increase awareness about the importance of talent recognition.\nTo expand opportunities through national and international initiatives.\nTo encourage participation without barriers of age, profession, or background.\nTo contribute towards strengthening India's culture of talent, achievement, and recognition." },
        { name: 'vision_icon', label: 'Card 3 icon', type: 'select', def: '🌟', options: ICONS },
        { name: 'vision_title', label: 'Card 3 title', max: 60, def: 'Our Vision' },
        { name: 'vision', label: 'Card 3 points (one per line)', type: 'textarea', max: 1500, rows: 6,
          def: "To build a future where every deserving individual has access to an opportunity.\nTo create a culture where talent is recognised with fairness and respect.\nTo strengthen India's identity by promoting talent across diverse fields.\nTo encourage future generations to believe in their abilities and pursue excellence.\nTo continue building platforms that inspire participation, recognition, and responsible growth." },
      ],
    },
    {
      key: 'cta',
      title: '5. Your Story Begins Here (bottom box)',
      fields: [
        { name: 'heading', label: 'Heading', max: 120, def: 'Your Story Begins Here' },
        { name: 'text', label: 'Text', type: 'textarea', rows: 4, max: 800, hint: '**word** = gold bold.',
          def: "Join **1,00,000+ dreamers** who trusted Forever Star India as their stage. Whether you're aiming for Miss Universe, Miss India, Mrs India, Super Woman, or Super Hero Awards — your story begins here." },
        { name: 'note', label: 'Gold line', max: 200, def: 'Auditions & Registrations are now open for the 2026 season.' },
        { name: 'button_label', label: 'Button text', max: 40, def: 'Register Now →' },
        { name: 'button_url', label: 'Button link', type: 'url', max: 300, def: '/quickapply' },
      ],
    },
  ],
};

// our-teams.php. People (photos, names, bios, quotes) come from Team Directory;
// these are the page's own texts and the three photos at the top.
SECTION_SCHEMAS['our-teams'] = [
  {
    key: 'hero',
    title: 'Top section: text, numbers & Season badge',
    image: { label: 'Left photo (small)', size: { w: 800, h: 1200, fit: 'cover' }, hint: 'Empty = the 5th Mentor’s photo.', fallback: 'mentor5' },
    fields: [
      { name: 'eyebrow', label: 'Small label', max: 60, def: 'The people behind FSIA' },
      { name: 'title', label: 'Heading', max: 60, def: 'THE FACES' },
      { name: 'title_italic', label: 'Heading, gold italic part', max: 60, def: 'behind every crown' },
      { name: 'intro', label: 'Paragraph', type: 'textarea', max: 500, def: "Founders, mentors, choreographers, anchors and storytellers. Together they guide Forever Star India's contestants from the first city audition to the grand coronation at Zee Studio Jaipur." },
      { name: 'stat_value', label: 'Third number', max: 20, def: '4,000+' },
      { name: 'stat_label', label: 'Third number: label', max: 30, def: 'Cities reached' },
      { name: 'seal_big', label: 'Round badge: big text', max: 8, def: 'S·8' },
      { name: 'seal_small', label: 'Round badge: small text', max: 16, def: 'Season' },
    ],
  },
  {
    key: 'hero_center',
    title: 'Top section: big centre photo',
    image: { label: 'Centre photo (big)', size: { w: 800, h: 1200, fit: 'cover' }, hint: 'Empty = the first Leadership member’s photo.', fallback: 'lead1' },
    fields: [],
  },
  {
    key: 'hero_right',
    title: 'Top section: right photo',
    image: { label: 'Right photo (small)', size: { w: 800, h: 1200, fit: 'cover' }, hint: 'Empty = the second Leadership member’s photo.', fallback: 'lead2' },
    fields: [],
  },
  {
    key: 'leadership',
    title: 'Leadership section',
    fields: [
      { name: 'eyebrow', label: 'Small label', max: 60, def: 'Official FSIA Board' },
      { name: 'heading', label: 'Heading', max: 60, def: 'Leadership' },
      { name: 'chip', label: 'Tag under each leader', max: 40, def: 'Forever Star India' },
    ],
  },
  {
    key: 'team_grid',
    title: 'Team section (all members)',
    fields: [
      { name: 'eyebrow', label: 'Small label', max: 60, def: 'Team FSIA' },
      { name: 'heading', label: 'Heading', max: 80, def: 'Mentors, Makers & Voices' },
      { name: 'subtitle', label: 'Line under the heading', max: 200, def: 'The experts who train, film, host and run every Forever Star India season.' },
    ],
  },
  {
    key: 'profiles',
    title: 'Social Media Profiles (also shown on the Contact page)',
    fields: [
      { name: 'visible', label: 'Show this section', type: 'select', options: [{ value: '', label: 'Show' }, { value: 'hide', label: 'Hide on all pages' }] },
      { name: 'mode', label: 'Which profiles', type: 'select', options: [
        { value: '', label: 'Pinned first, then newest (changes on every visit)' },
        { value: 'pinned', label: 'Only the pinned profiles below' },
      ] },
      { name: 'types', label: 'Profile types (for the automatic part)', type: 'select', options: [
        { value: '', label: 'Pageant and awardees, mixed' },
        { value: 'pageant', label: 'Only pageant (Miss / Mrs / Teen India)' },
        { value: 'awardees', label: 'Only awardees' },
      ] },
      { name: 'count', label: 'How many profiles', type: 'number', min: 1, placeholder: '20', hint: 'Between 4 and 40. 4 per row on desktop.' },
      { name: 'pinned', label: 'Pinned profiles (one link per line)', type: 'textarea', rows: 4, max: 4000,
        placeholder: 'https://www.fsia.in/miss-india-contestant-2025-honey-tadepalligudem', hint: 'Open the person’s profile on the website and paste its link. Shown first, in this order.' },
      { name: 'hidden', label: 'Never show these profiles (one link per line)', type: 'textarea', rows: 3, max: 4000,
        placeholder: 'https://starindia.fsia.in/mrs-india-finalist-from-bhiwani' },
      { name: 'badge', label: 'Small label', max: 60, def: 'Official Participants' },
      { name: 'heading', label: 'Heading', max: 80, def: 'Social Media Profiles' },
      { name: 'button_text', label: 'Button text', max: 40, def: 'View More Profiles' },
      { name: 'button_url', label: 'Button link', type: 'url', max: 300, def: 'https://starindia.fsia.in/' },
      { name: 'button_note', label: 'Line under the button', max: 80, def: 'Browse the full Star India directory' },
    ],
  },
  {
    key: 'cta',
    title: 'Bottom section',
    fields: [
      { name: 'eyebrow', label: 'Small label', max: 60, def: 'Season 2026 is open' },
      { name: 'heading', label: 'Heading', max: 60, def: 'Your stage is' },
      { name: 'heading_italic', label: 'Heading, gold italic part', max: 40, def: 'waiting' },
      { name: 'text', label: 'Paragraph', type: 'textarea', max: 500, def: 'Audition in your city, train with this team, and walk the national stage at Zee Studio Jaipur. Questions? Our helpline is open Mon–Sat, 10am–7pm IST.' },
      { name: 'link1_title', label: 'Link 1: title', max: 60, def: 'Quick Apply' },
      { name: 'link1_sub', label: 'Link 1: small text', max: 80, def: 'Register for the 2026 season' },
      { name: 'link1_url', label: 'Link 1: link', type: 'url', max: 300, def: 'https://www.fsia.in/quickapply' },
      { name: 'link2_title', label: 'Link 2: title', max: 60, def: 'Become a Channel Partner' },
      { name: 'link2_sub', label: 'Link 2: small text', max: 80, def: 'Bring FSIA to your city' },
      { name: 'link2_url', label: 'Link 2: link', type: 'url', max: 300, def: 'https://www.fsia.in/channel-partner' },
      { name: 'link3_title', label: 'Link 3: title', max: 60, def: '+91-99832-86999' },
      { name: 'link3_sub', label: 'Link 3: small text', max: 80, def: 'Call the FSIA helpline' },
      { name: 'link3_url', label: 'Link 3: link', type: 'url', max: 300, def: 'tel:+919983286999' },
    ],
  },
];

// The five page sections of our-teams.php, then further groups a member can also belong to.
export const TEAM_CORE_CATEGORIES = ['Leadership', 'Core Team', 'Mentors', 'Anchors', 'Media'];
export const TEAM_CATEGORIES = [...TEAM_CORE_CATEGORIES, 'Jury', 'Fashion Designers', 'Makeup Artists', 'Special Guests', 'Performers', 'Influencers', 'State Directors', 'National Winners', 'Achievers'];
/** "Leadership, Jury" -> ['Leadership', 'Jury']: a member can sit in several categories. */
export const splitCategories = (value) => String(value || '').split(',').map((s) => s.trim()).filter(Boolean);
export const SOCIAL_NETWORKS = ['instagram', 'facebook', 'twitter', 'linkedin', 'youtube', 'website'];
