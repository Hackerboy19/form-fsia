// The five pages the CMS manages, and the editable sections of the Home and
// About pages. Each section is stored as one page_sections row: `fields` become
// keys of its JSON `content`, and `image` adds an image upload (image_url).
// To add a field or a section, edit this file: no database change is needed.

export const PAGES = [
  { slug: 'index', label: 'Home', file: 'index.php' },
  { slug: 'about', label: 'About', file: 'about.php' },
  { slug: 'our-teams', label: 'Our Team', file: 'our-teams.php', preview: '/our-teams.php' },
  { slug: 'news-coverage', label: 'News Coverage', file: 'news-coverage.php' },
  { slug: 'special-news-coverage', label: 'Special Coverage', file: 'special-news-coverage.php' },
];

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
  about: [
    {
      key: 'hero',
      title: 'Page header',
      image: { label: 'Header image', size: { w: 1600, h: 700, fit: 'cover' } },
      fields: [
        { name: 'eyebrow', label: 'Small label', max: 80 },
        { name: 'title', label: 'Title', max: 140, required: true, placeholder: 'About Forever Star India' },
        { name: 'subtitle', label: 'Subtitle', type: 'textarea', max: 400 },
      ],
    },
    {
      key: 'story',
      title: 'Our story',
      image: { label: 'Story image', size: { w: 1200, h: 900, fit: 'cover' } },
      fields: [
        { name: 'heading', label: 'Heading', max: 120 },
        { name: 'body', label: 'Story', type: 'textarea', max: 5000, rows: 8 },
      ],
    },
    {
      key: 'mission',
      title: 'Mission & vision',
      fields: [
        { name: 'mission', label: 'Mission', type: 'textarea', max: 800 },
        { name: 'vision', label: 'Vision', type: 'textarea', max: 800 },
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
    image: { label: 'Left photo (small)', size: { w: 800, h: 1200, fit: 'cover' }, hint: 'Empty = the 5th Mentor’s photo.' },
    fields: [
      { name: 'eyebrow', label: 'Small label', max: 60, placeholder: 'The people behind FSIA' },
      { name: 'title', label: 'Heading', max: 60, placeholder: 'The Faces' },
      { name: 'title_italic', label: 'Heading, gold italic part', max: 60, placeholder: 'behind every crown' },
      { name: 'intro', label: 'Paragraph', type: 'textarea', max: 500, placeholder: 'Founders, mentors, choreographers, anchors and storytellers…' },
      { name: 'stat_value', label: 'Third number', max: 20, placeholder: '4,000+' },
      { name: 'stat_label', label: 'Third number: label', max: 30, placeholder: 'Cities reached' },
      { name: 'seal_big', label: 'Round badge: big text', max: 8, placeholder: 'S·8' },
      { name: 'seal_small', label: 'Round badge: small text', max: 16, placeholder: 'Season' },
    ],
  },
  {
    key: 'hero_center',
    title: 'Top section: big centre photo',
    image: { label: 'Centre photo (big)', size: { w: 800, h: 1200, fit: 'cover' }, hint: 'Empty = the first Leadership member’s photo.' },
    fields: [],
  },
  {
    key: 'hero_right',
    title: 'Top section: right photo',
    image: { label: 'Right photo (small)', size: { w: 800, h: 1200, fit: 'cover' }, hint: 'Empty = the second Leadership member’s photo.' },
    fields: [],
  },
  {
    key: 'leadership',
    title: 'Leadership section',
    fields: [
      { name: 'eyebrow', label: 'Small label', max: 60, placeholder: 'Official FSIA Board' },
      { name: 'heading', label: 'Heading', max: 60, placeholder: 'Leadership' },
      { name: 'chip', label: 'Tag under each leader', max: 40, placeholder: 'Forever Star India' },
    ],
  },
  {
    key: 'team_grid',
    title: 'Team section (all members)',
    fields: [
      { name: 'eyebrow', label: 'Small label', max: 60, placeholder: 'Team FSIA' },
      { name: 'heading', label: 'Heading', max: 80, placeholder: 'Mentors, Makers & Voices' },
      { name: 'subtitle', label: 'Line under the heading', max: 200, placeholder: 'The experts who train, film, host and run every Forever Star India season.' },
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
      { name: 'badge', label: 'Small label', max: 60, placeholder: 'Official Participants' },
      { name: 'heading', label: 'Heading', max: 80, placeholder: 'Social Media Profiles' },
      { name: 'button_text', label: 'Button text', max: 40, placeholder: 'View More Profiles' },
      { name: 'button_url', label: 'Button link', type: 'url', max: 300, placeholder: 'https://starindia.fsia.in/' },
      { name: 'button_note', label: 'Line under the button', max: 80, placeholder: 'Browse the full Star India directory' },
    ],
  },
  {
    key: 'cta',
    title: 'Bottom section',
    fields: [
      { name: 'eyebrow', label: 'Small label', max: 60, placeholder: 'Season 2026 is open' },
      { name: 'heading', label: 'Heading', max: 60, placeholder: 'Your stage is' },
      { name: 'heading_italic', label: 'Heading, gold italic part', max: 40, placeholder: 'waiting' },
      { name: 'text', label: 'Paragraph', type: 'textarea', max: 500 },
      { name: 'link1_title', label: 'Link 1: title', max: 60, placeholder: 'Quick Apply' },
      { name: 'link1_sub', label: 'Link 1: small text', max: 80, placeholder: 'Register for the 2026 season' },
      { name: 'link1_url', label: 'Link 1: link', type: 'url', max: 300, placeholder: 'https://www.fsia.in/quickapply' },
      { name: 'link2_title', label: 'Link 2: title', max: 60, placeholder: 'Become a Channel Partner' },
      { name: 'link2_sub', label: 'Link 2: small text', max: 80, placeholder: 'Bring FSIA to your city' },
      { name: 'link2_url', label: 'Link 2: link', type: 'url', max: 300, placeholder: 'https://www.fsia.in/channel-partner' },
      { name: 'link3_title', label: 'Link 3: title', max: 60, placeholder: '+91-99832-86999' },
      { name: 'link3_sub', label: 'Link 3: small text', max: 80, placeholder: 'Call the FSIA helpline' },
      { name: 'link3_url', label: 'Link 3: link', type: 'url', max: 300, placeholder: 'tel:+919983286999' },
    ],
  },
];

export const TEAM_CATEGORIES = ['Leadership', 'Core Team', 'Mentors', 'Anchors', 'Media'];
export const SOCIAL_NETWORKS = ['instagram', 'facebook', 'twitter', 'linkedin', 'youtube', 'website'];
