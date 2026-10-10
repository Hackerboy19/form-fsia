// The five pages the CMS manages, and the editable sections of the Home and
// About pages. Each section is stored as one page_sections row: `fields` become
// keys of its JSON `content`, and `image` adds an image upload (image_url).
// To add a field or a section, edit this file: no database change is needed.

export const PAGES = [
  { slug: 'index', label: 'Home', file: 'index.php' },
  { slug: 'about', label: 'About', file: 'about.php' },
  { slug: 'our-teams', label: 'Our Team', file: 'our-teams.php' },
  { slug: 'news-coverage', label: 'News Coverage', file: 'news-coverage.php' },
  { slug: 'special-news-coverage', label: 'Special Coverage', file: 'special-news-coverage.php' },
];

export const SECTION_SCHEMAS = {
  index: [
    {
      key: 'hero',
      title: 'Hero banner',
      image: { label: 'Hero image', hint: 'Wide image, at least 1600 × 900' },
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
      image: { label: 'Side image' },
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
      image: { label: 'Header image', hint: 'Wide image, at least 1600 × 700' },
      fields: [
        { name: 'eyebrow', label: 'Small label', max: 80 },
        { name: 'title', label: 'Title', max: 140, required: true, placeholder: 'About Forever Star India' },
        { name: 'subtitle', label: 'Subtitle', type: 'textarea', max: 400 },
      ],
    },
    {
      key: 'story',
      title: 'Our story',
      image: { label: 'Story image' },
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

export const TEAM_CATEGORIES = ['Leadership', 'Core Team', 'Mentors', 'Anchors', 'Media'];
export const SOCIAL_NETWORKS = ['instagram', 'facebook', 'twitter', 'linkedin', 'youtube', 'website'];
