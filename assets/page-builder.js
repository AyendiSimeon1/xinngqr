(function(){
  const state = structuredClone(window.initialPageState || {});
  const config = window.pageBuilderConfig || {};
  let dirty = false;

  const themes = {
    default: { bg:'#FFFAF6', header:'#26282C', text:'#26282C', desc:'#26282C', block:'#0A9994', blockText:'#FFFAF6' },
    blue: { bg:'#EAF5FF', header:'#9ED4FF', text:'#102A43', desc:'#334E68', block:'#2F73FF', blockText:'#FFFFFF' },
    aqua: { bg:'#E9FBFA', header:'#5DD5D0', text:'#063B3D', desc:'#315B5D', block:'#0A9994', blockText:'#FFFFFF' },
    warm: { bg:'#FFF5EA', header:'#FFB000', text:'#2B2118', desc:'#584637', block:'#FF7A00', blockText:'#FFFFFF' },
    pink: { bg:'#FFF0F4', header:'#D9265C', text:'#2B1520', desc:'#694052', block:'#D9265C', blockText:'#FFFFFF' },
    navy: { bg:'#EEF2FF', header:'#233D8B', text:'#0F172A', desc:'#334155', block:'#233D8B', blockText:'#FFFFFF' },
    teal: { bg:'#E6F8F5', header:'#064E5F', text:'#062B33', desc:'#315A60', block:'#064E5F', blockText:'#FFFFFF' },
    dark: { bg:'#111111', header:'#2C2C2C', text:'#FFFFFF', desc:'#E6E6E6', block:'#2E2E2E', blockText:'#FFFFFF' }
  };
  const layouts = ['simple','curved','header-image','compact','minimal'];
  const platforms = ['Instagram','Facebook','YouTube','WhatsApp','Telegram','TikTok','Snapchat','Spotify','Email','X','LinkedIn','Website'];
  const pageModes = {
    creator: {
      name: 'Personal Page',
      help: 'For personal brands, creators, freelancers, consultants, and individuals.',
      headerLabel: 'Profile',
      empty: 'Add links, content, bookings, and social blocks to build your personal page.',
      previewTitle: 'Page title',
      previewDescription: 'Your page description',
      suggestedTypes: ['link','social','image','video','youtube','music','shop','subscribe','contact','booking','text','qr','short_link']
    },
    corporate: {
      name: 'Company Page',
      help: 'For companies, events, documents, meetings, products, teams, and business inquiries.',
      headerLabel: 'Company Identity',
      empty: 'Add business actions, documents, meetings, and contact routing to build your company page.',
      previewTitle: 'Company name',
      previewDescription: 'One-line value proposition, location, and business category.',
      suggestedTypes: ['cta','capability','document_hub','meeting_booking','event_countdown','contact_routing','team_member','product_catalogue','investor_material','file','short_link','qr','text','image']
    }
  };
  const blockTypes = {
    link: { label:'Link', title:'Follow me', destination:'https://example.com' },
    image: { label:'Image', title:'Untitled image' },
    text: { label:'Text', title:'Text block', description:'Add supporting copy.' },
    social: { label:'Social', title:'Follow', destination:'https://example.com' },
    video: { label:'Video', title:'Watch video', destination:'https://example.com/video' },
    youtube: { label:'YouTube', title:'Watch latest content', destination:'https://youtube.com' },
    music: { label:'Music', title:'Listen now', destination:'https://example.com/music' },
    shop: { label:'Shop / Product', title:'Shop now', destination:'https://example.com/shop' },
    subscribe: { label:'Subscribe', title:'Subscribe', destination:'https://example.com/subscribe' },
    contact: { label:'Contact', title:'Contact me', destination:'mailto:hello@example.com' },
    booking: { label:'Booking', title:'Book me', destination:'https://example.com/book' },
    qr: { label:'QR Code', title:'QR / Short link' },
    short_link: { label:'Short Link', title:'Short link', destination:'https://example.com' },
    cta: { label:'CTA block', title:'Request Quote', destination:'https://example.com/contact' },
    capability: { label:'Capability card', title:'Capabilities', description:'Show core services and proof points.' },
    document_hub: { label:'Document hub', title:'Download Company Profile', destination:'https://example.com/profile' },
    meeting_booking: { label:'Meeting booking', title:'Book Technical Meeting', destination:'https://example.com/meeting' },
    event_countdown: { label:'Event countdown', title:'Visit us at the event', description:'Add event details and timing.' },
    contact_routing: { label:'Contact routing', title:'Contact Business Development', destination:'mailto:sales@example.com' },
    team_member: { label:'Team member', title:'Contact Technical Team', destination:'mailto:technical@example.com' },
    product_catalogue: { label:'Product catalogue', title:'View Product Catalogue', destination:'https://example.com/catalogue' },
    investor_material: { label:'Investor / partner materials', title:'Investor Materials', destination:'https://example.com/investors' },
    file: { label:'File download', title:'Download file', destination:'https://example.com/file.pdf' },
    tip_jar: { label:'Tip jar', title:'Support my work', destination:'https://example.com/support' },
    social_feed: { label:'Social feed', title:'Latest updates', destination:'https://example.com/feed' }
  };
  function defaultCorporate() {
    return {
      header_photo: '',
      logo: '',
      company_name: state.title || 'Company Page',
      description: state.description || 'Company profile, documents, meetings, and business inquiries.',
      company_website: '',
      cards_title: 'Core Capabilities',
      cards_lede: 'Integrated technical solutions designed for any environment and high-risk industrial operations.',
      actions_title: 'What would you like to do?',
      actions_lede: '',
      event_register_title: 'Event Registration',
      hero_primary_cta_label: '',
      hero_primary_cta_url: '',
      quote_title: 'Request for Quote',
      quote_description: 'Tell us about your project and we will follow up soon.',
      quote_button_label: 'Submit Request',
      contact: { meeting_link:'', brochure_link:'', phone:'', email:'', whatsapp:'' },
      specialties: [],
      locations: [],
      links: [],
      socials: [],
      event: { title:'', description:'', start_at:'', end_at:'', location:'', city:'', countdown:true, book_link:'', brochure_link:'', register:true, card_color:'#062947', button_label:'Join Webinar' },
      cards: [
        { title:'', type:'text', description:'', cta_label:'Learn More', link:'', fill_type:'color', fill_color:'#06111E', gradient_start:'#06111E', gradient_end:'#0A9994', photo:'', outline_color:'#0A9994', outline_weight:0 },
        { title:'', type:'text', description:'', cta_label:'Learn More', link:'', fill_type:'color', fill_color:'#06111E', gradient_start:'#06111E', gradient_end:'#0A9994', photo:'', outline_color:'#0A9994', outline_weight:0 },
        { title:'', type:'text', description:'', cta_label:'Learn More', link:'', fill_type:'color', fill_color:'#06111E', gradient_start:'#06111E', gradient_end:'#0A9994', photo:'', outline_color:'#0A9994', outline_weight:0 }
      ],
      buttons: [{ label:'', url:'', button_color:'#1979BF', text_color:'#FFFFFF' }],
      team: {
        title: 'Team',
        description: '',
        members: [
          { photo:'', name:'', title:'', phone:'', email:'', linkedin:'' },
          { photo:'', name:'', title:'', phone:'', email:'', linkedin:'' },
          { photo:'', name:'', title:'', phone:'', email:'', linkedin:'' }
        ]
      }
    };
  }

  const $ = (s, root=document) => root.querySelector(s);
  const $$ = (s, root=document) => Array.from(root.querySelectorAll(s));
  const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  const uid = () => 'tmp-' + Math.random().toString(36).slice(2);
  let analyticsState = { loading: false, data: null, error: null, pageId: null };
  const markDirty = () => { dirty = true; $('#publish-page').disabled = false; $('#publish-page').textContent = 'Publish changes'; };
  const activeBlocks = () => (state.blocks || []).filter(b => b.is_active !== false);
  const mode = () => pageModes[state.page_type] || pageModes.creator;
  const blockLabel = type => blockTypes[type]?.label || String(type || 'link').replace(/_/g, ' ');
  function mergeCorporate(raw) {
    const base = defaultCorporate();
    const incoming = raw && typeof raw === 'object' ? raw : {};
    const merged = { ...base, ...incoming };
    merged.contact = { ...base.contact, ...(incoming.contact || {}) };
    merged.event = { ...base.event, ...(incoming.event || {}) };
    merged.team = { ...base.team, ...(incoming.team || {}) };
    merged.team.members = [...(incoming.team?.members || []), ...base.team.members].slice(0, 12);
    merged.links = [...(incoming.links || [])].slice(0, 3).map(x => ({ label:x?.label || '', url:x?.url || '' }));
    merged.socials = [...(incoming.socials || [])].slice(0, 6).map(x => ({ platform:x?.platform || '', url:x?.url || '' }));
    merged.company_website = incoming.company_website || base.company_website || '';
    merged.cards = [...(incoming.cards || []), ...base.cards].slice(0, 12).map(x => ({ ...base.cards[0], ...(x || {}) }));
    merged.specialties = [...(incoming.specialties || [])].slice(0, 6).map(x => typeof x === 'string' ? x : (x?.label || ''));
    merged.locations = [...(incoming.locations || [])].slice(0, 3).map(x => typeof x === 'string' ? x : (x?.label || ''));
    // Merge simple string section fields
    merged.cards_title = incoming.cards_title || base.cards_title;
    merged.cards_lede = incoming.cards_lede || base.cards_lede;
    merged.actions_title = incoming.actions_title || base.actions_title;
    merged.actions_lede = incoming.actions_lede || base.actions_lede;
    merged.event_register_title = incoming.event_register_title || base.event_register_title;
    merged.buttons = [...(incoming.buttons || []), ...base.buttons].slice(0, 8).map(x => ({ ...base.buttons[0], ...(x || {}) }));
    merged.specialties = [...(incoming.specialties || []), ...base.specialties].slice(0, 6).map(x => typeof x === 'string' ? x : (x?.label || ''));
    merged.locations = [...(incoming.locations || []), ...base.locations].slice(0, 3).map(x => typeof x === 'string' ? x : (x?.label || ''));
    return merged;
  }
  state.corporate = mergeCorporate(state.corporate);

  function fontFamily() {
    return state.font === 'system' ? 'Inter, system-ui, sans-serif' : `${state.font}, Inter, system-ui, sans-serif`;
  }

  function headerStyle() {
    const h = state.header || {};
    if (h.mode === 'image' && h.image) return `background-image:url('${h.image}');background-size:${h.fit === 'repeat' ? 'auto' : h.fit || 'cover'};background-repeat:${h.fit === 'repeat' ? 'repeat' : 'no-repeat'};background-position:center;`;
    if (h.mode === 'gradient') return `background:linear-gradient(135deg, ${h.gradient_start || '#26282C'}, ${h.gradient_end || '#0A9994'});`;
    return `background:${h.color || '#26282C'};`;
  }

  function backgroundStyle() {
    const bg = state.background || {};
    if (bg.mode === 'image' && bg.image) return `background-image:url('${bg.image}');background-size:cover;background-position:center;`;
    if (bg.mode === 'gradient') return `background:linear-gradient(180deg, ${bg.gradient_start || '#FFFAF6'}, ${bg.gradient_end || '#FFFFFF'});`;
    return `background:${bg.color || '#FFFAF6'};`;
  }

  function socialLabel(platform) {
    const map = { Instagram:'IG', Facebook:'FB', YouTube:'YT', WhatsApp:'WA', Telegram:'TG', TikTok:'TT', Snapchat:'SC', Spotify:'SP', Email:'@', X:'X', LinkedIn:'IN', Website:'WEB' };
    return map[platform] || platform.slice(0,2).toUpperCase();
  }

  function socialIcon(platform) {
    const p = String(platform || '').toLowerCase();
    if (p.includes('instagram')) return '<i class="fa-brands fa-instagram" aria-hidden="true"></i>';
    if (p.includes('facebook')) return '<i class="fa-brands fa-facebook-f" aria-hidden="true"></i>';
    if (p.includes('youtube') || p.includes('yt')) return '<i class="fa-brands fa-youtube" aria-hidden="true"></i>';
    if (p.includes('whatsapp') || p.includes('wa')) return '<i class="fa-brands fa-whatsapp" aria-hidden="true"></i>';
    if (p.includes('telegram') || p.includes('tg')) return '<i class="fa-brands fa-telegram" aria-hidden="true"></i>';
    if (p.includes('tiktok') || p.includes('tt')) return '<i class="fa-brands fa-tiktok" aria-hidden="true"></i>';
    if (p.includes('snap')) return '<i class="fa-brands fa-snapchat" aria-hidden="true"></i>';
    if (p.includes('spotify') || p.includes('sp')) return '<i class="fa-brands fa-spotify" aria-hidden="true"></i>';
    if (p.includes('email') || p.includes('mail')) return '<i class="fa-regular fa-envelope" aria-hidden="true"></i>';
    if (p.includes('x') || p.includes('twitter')) return '<i class="fa-brands fa-x-twitter" aria-hidden="true"></i>';
    if (p.includes('linkedin') || p.includes('linked')) return '<i class="fa-brands fa-linkedin-in" aria-hidden="true"></i>';
    if (p.includes('website') || p.includes('web')) return '<i class="fa-solid fa-globe" aria-hidden="true"></i>';
    return '<i class="fa-solid fa-link" aria-hidden="true"></i>';
  }

  function renderCorporatePreview(preview) {
    const c = state.corporate = mergeCorporate(state.corporate);
    const contact = c.contact || {};
    const event = c.event || {};
    const cards = (c.cards || []).filter(card => card.title).slice(0, 4);
    const socials = (c.socials || []).filter(s => s.platform && s.url).slice(0, 6);
    const specialties = (c.specialties || []).filter(Boolean).slice(0, 3);
    const locations = (c.locations || []).filter(Boolean).slice(0, 3);
    const actionItems = [];
    if (contact.meeting_link) actionItems.push('<span>Book Meeting</span>');
    if (contact.brochure_link) actionItems.push('<span>Download Brochure</span>');
    if (c.quote_title || c.quote_description || c.quote_button_label) actionItems.push('<span>Request Quote</span>');
    const header = c.header_photo ? `background-image:linear-gradient(rgba(0,27,52,.58),rgba(0,27,52,.58)),url('${esc(c.header_photo)}')` : 'background:#d9d9d9';
    const logo = c.logo || state.profile_image;
    preview.style.cssText = 'background:#f4f6f8;font-family:Inter,system-ui,sans-serif;';
    preview.innerHTML = `
      <div class="corp-pv">
        <div class="corp-pv-band" style="${header};"></div>
        <section class="corp-pv-hero">
          <div class="corp-pv-logo">${logo ? `<img src="${esc(logo)}" alt="">` : esc((c.company_name || state.title || 'CO').slice(0,2).toUpperCase())}</div>
          <h2>${esc(c.company_name || state.title || 'Company Page')}</h2>
          <small>${esc(state.slug || 'page-id')}</small>
          <p>${esc(c.description || state.description || 'Company profile and business actions.')}</p>
          ${specialties.length ? `<div class="corp-pv-tags">${specialties.map(v => `<span>${esc(v)}</span>`).join('')}</div>` : ''}
          ${locations.length ? `<div class="corp-pv-tags">${locations.map(v => `<span>${esc(v)}</span>`).join('')}</div>` : ''}
          ${c.company_website ? `<div class="corp-pv-actions"><a>${esc('Website')}</a></div>` : ''}
          ${(contact.whatsapp || contact.phone || contact.email) ? `<div class="corp-pv-actions">
            ${contact.whatsapp ? `<a>WA</a>` : ''}
            ${contact.phone ? `<a>PH</a>` : ''}
            ${contact.email ? `<a>EM</a>` : ''}
          </div>` : ''}
        </section>
        ${event.title ? `<section class="corp-pv-event" style="background:${esc(event.card_color || '#062947')}"><strong>${esc(event.title)}</strong><small>${esc([event.city, event.location].filter(Boolean).join(' - '))}</small></section>` : ''}
        <section class="corp-pv-section"><h3>What would you like to do?</h3><div class="corp-pv-grid">
          ${contact.meeting_link ? `<span>Book Meeting</span>` : ''}
          ${contact.brochure_link ? `<span>Download Brochure</span>` : ''}
          <span>Request Quote</span>
        </div></section>
        <section class="corp-pv-section"><h3>Cards</h3>${cards.map(card => `<article class="corp-pv-card"><strong>${esc(card.title)}</strong><small>${esc(card.description || card.type)}</small></article>`).join('') || '<article class="corp-pv-card"><strong>Add cards</strong><small>Capability, PDF, video, or text cards appear here.</small></article>'}</section>
        ${socials.length ? `<div class="corp-pv-socials">${socials.map(s => `<span>${socialIcon(s.platform || 'Link')}</span>`).join('')}</div>` : ''}
      </div>`;
    $('#builder-url-text').textContent = `${config.publicBase.replace(/^https?:\/\//,'')}/${state.slug || 'page'}`;
  }

  function renderPreview() {
    const preview = $('#page-preview');
    if ((state.page_type || 'creator') === 'corporate') {
      renderCorporatePreview(preview);
      return;
    }
    const socials = (state.socials || []).filter(s => s.is_active !== false && s.url);
    const socialsHtml = socials.map(s => `<span>${socialIcon(s.platform)}</span>`).join('');
    const blockStyle = state.block_style || {};
    const shapeClass = 'shape-' + (blockStyle.shape || 'rounded');
    const shadowClass = 'shadow-' + (blockStyle.shadow || 'soft');
    const blocksHtml = activeBlocks().map(block => {
      const type = block.type || 'link';
      if (type === 'image') {
        return `<a class="pb-block image ${shapeClass} ${shadowClass}" style="color:${esc(blockStyle.block_text_color)};background:${esc(blockStyle.block_color)}" href="${esc(block.destination_url || '#')}">${block.image_path ? `<img src="${esc(block.image_path)}" alt="">` : ''}<strong>${esc(block.title || 'Untitled image')}</strong>${block.description ? `<small>${esc(block.description)}</small>` : ''}</a>`;
      }
      if (type === 'text') return `<div class="pb-block text ${shapeClass} ${shadowClass}" style="color:${esc(blockStyle.block_text_color)};background:${esc(blockStyle.block_color)}"><strong>${esc(block.title || 'Text block')}</strong><small>${esc(block.description || '')}</small></div>`;
      if (type === 'social') return `<a class="pb-block ${shapeClass} ${shadowClass}" style="color:${esc(blockStyle.block_text_color)};background:${esc(blockStyle.block_color)}" href="${esc(block.destination_url || '#')}"><strong>${esc(block.title || 'Social')}</strong></a>`;
      if (type === 'qr') return `<a class="pb-block ${shapeClass} ${shadowClass}" style="color:${esc(blockStyle.block_text_color)};background:${esc(blockStyle.block_color)}" href="${esc(block.destination_url || '#')}"><strong>${esc(block.title || 'QR / Short link')}</strong><small>${esc(block.description || '')}</small></a>`;
      return `<a class="pb-block ${shapeClass} ${shadowClass}" style="color:${esc(blockStyle.block_text_color)};background:${esc(blockStyle.block_color)}" href="${esc(block.destination_url || '#')}"><strong>${esc(block.title || 'Link block')}</strong>${block.description ? `<small>${esc(block.description)}</small>` : ''}</a>`;
    }).join('');

    preview.style.cssText = `${backgroundStyle()}font-family:${fontFamily()};`;
    preview.innerHTML = `
      <div class="pb-header layout-${esc(state.layout || 'simple')}" style="${headerStyle()}">
        <div class="pb-avatar">${state.profile_image ? `<img src="${esc(state.profile_image)}" alt="">` : `<i class="fa-regular fa-image"></i>`}</div>
      </div>
      <div class="pb-content">
        <h2 style="color:${esc(state.text_color || '#26282C')}">${esc(state.title || mode().previewTitle)}</h2>
        <p style="color:${esc(state.description_color || '#26282C')}">${esc(state.description || mode().previewDescription)}</p>
        ${state.social_placement !== 'bottom' ? `<div class="pb-socials style-${esc(state.social_style || 'original')}">${socialsHtml}</div>` : ''}
        <div class="pb-blocks">${blocksHtml || `<div class="pb-empty">${esc(mode().empty)}</div>`}</div>
        ${state.social_placement === 'bottom' ? `<div class="pb-socials style-${esc(state.social_style || 'original')}">${socialsHtml}</div>` : ''}
        ${state.branding?.hide_xinng_logo ? '' : `<div class="pb-brand">Powered by xin.ng</div>`}
      </div>`;
    $('#builder-url-text').textContent = `${config.publicBase.replace(/^https?:\/\//,'')}/${state.slug || 'page'}`;
  }

  function renderBlocks() {
    const list = $('#block-list');
    list.innerHTML = (state.blocks || []).map((block, index) => `
      <article class="builder-block ${block.is_active === false ? 'disabled' : ''}" data-index="${index}">
        <div class="drag">::</div>
        <div class="builder-block-main">
          <div class="builder-block-head"><strong>${esc(blockLabel(block.type || 'link'))}</strong><span>${block.is_active === false ? 'Disabled' : 'Active'}</span></div>
          <div class="builder-block-edit">
            <label>Title<input data-field="title" value="${esc(block.title || '')}"></label>
            <label>Destination / URL<input data-field="destination_url" value="${esc(block.destination_url || '')}"></label>
            <label>Description<textarea data-field="description">${esc(block.description || '')}</textarea></label>
            ${block.type === 'image' ? `<label class="small-btn">Image<input data-field="image_file" type="file" accept="image/*" hidden></label>` : ''}
          </div>
        </div>
        <div class="builder-block-actions">
          <button data-action="up" type="button"><i class="fa-solid fa-arrow-up"></i></button>
          <button data-action="down" type="button"><i class="fa-solid fa-arrow-down"></i></button>
          <button data-action="toggle" type="button"><i class="fa-solid fa-toggle-${block.is_active === false ? 'off' : 'on'}"></i></button>
          <button data-action="delete" type="button"><i class="fa-regular fa-trash-can"></i></button>
        </div>
      </article>`).join('');
  }

  function renderDesignControls() {
    const pageTypeSelect = $('#page-type-select');
    if (pageTypeSelect) pageTypeSelect.value = state.page_type || 'creator';
    const typeHelp = $('#page-type-help');
    if (typeHelp) typeHelp.textContent = mode().help;
    const profileTitle = $('#profile-section-title');
    if (profileTitle) profileTitle.textContent = mode().headerLabel;
    const modeNote = $('#builder-mode-note');
    if (modeNote) modeNote.innerHTML = `<strong>${esc(mode().name)}</strong><span>${esc(mode().help)}</span>`;
    $('#page-title').value = state.title || '';
    $('#page-description').value = state.description || '';
    $('#page-slug').value = state.slug || '';
    $('#header-color').value = state.header?.color || '#26282C';
    $('#header-gradient-start').value = state.header?.gradient_start || '#26282C';
    $('#header-gradient-end').value = state.header?.gradient_end || '#0A9994';
    $('#header-fit').value = state.header?.fit || 'cover';
    $('#background-color').value = state.background?.color || '#FFFAF6';
    $('#background-gradient-start').value = state.background?.gradient_start || '#FFFAF6';
    $('#background-gradient-end').value = state.background?.gradient_end || '#FFFFFF';
    $('#title-color').value = state.text_color || '#26282C';
    $('#description-color').value = state.description_color || '#26282C';
    $('#font-select').value = state.font || 'system';
    $('#block-color').value = state.block_style?.block_color || '#0A9994';
    $('#block-text-color').value = state.block_style?.block_text_color || '#FFFAF6';
    $('#hide-branding').checked = !!state.branding?.hide_xinng_logo;
    $('#title-count').textContent = `${(state.title || '').length}/32`;
    $('#desc-count').textContent = `${(state.description || '').length}/80`;
    $('#profile-image-preview').innerHTML = state.profile_image ? `<img src="${esc(state.profile_image)}" alt="">` : `<i class="fa-regular fa-image"></i>`;

    $('#theme-row').innerHTML = Object.keys(themes).map(name => `<button class="theme-dot ${state.theme === name ? 'active' : ''}" data-theme="${name}" style="--theme:${themes[name].header}"></button>`).join('');
    $('#layout-row').innerHTML = layouts.map(name => `<button class="layout-card ${state.layout === name ? 'active' : ''}" data-layout="${name}"><span></span><strong>${name.replace('-', ' ')}</strong></button>`).join('');
    $('#social-picker').innerHTML = platforms.map(p => {
      const existing = (state.socials || []).find(s => s.platform === p);
      return `<label class="social-chip ${existing ? 'active' : ''}"><input type="checkbox" data-platform="${p}" ${existing ? 'checked' : ''}>${socialLabel(p)} ${p}</label>${existing ? `<input class="social-url" data-platform-url="${p}" value="${esc(existing.url || '')}" placeholder="${p} URL">` : ''}`;
    }).join('');
    $('#social-style-row').innerHTML = ['original','black','white'].map(v => `<label><input type="radio" name="socialStyle" value="${v}" ${state.social_style === v ? 'checked' : ''}> ${v}</label>`).join('');
    $('#social-placement-row').innerHTML = ['top','bottom'].map(v => `<label><input type="radio" name="socialPlacement" value="${v}" ${state.social_placement === v ? 'checked' : ''}> ${v} of page</label>`).join('');
    $('#block-shape-row').innerHTML = ['pill','rounded','sharp','outline-pill','outline-rectangle'].map(v => `<button class="shape-sample ${state.block_style?.shape === v ? 'active' : ''}" data-shape="${v}">${v}</button>`).join('');
    $('#block-shadow-row').innerHTML = ['none','soft','hard'].map(v => `<button class="shadow-sample ${state.block_style?.shadow === v ? 'active' : ''}" data-shadow="${v}">${v}</button>`).join('');
    $$('.segmented').forEach(group => {
      const [obj, key] = group.dataset.bind.split('.');
      $$('button', group).forEach(btn => btn.classList.toggle('active', state[obj]?.[key] === btn.dataset.value));
    });
    renderBlockTypeMenu();
  }

  function renderBlockTypeMenu() {
    const suggested = $('#suggested-block-types');
    const more = $('#more-block-types');
    if (!suggested || !more) return;
    const suggestedTypes = mode().suggestedTypes || [];
    const allTypes = Object.keys(blockTypes);
    const buttonHtml = type => `<button data-type="${esc(type)}" type="button">${esc(blockLabel(type))}</button>`;
    suggested.innerHTML = suggestedTypes.map(buttonHtml).join('');
    more.innerHTML = allTypes.filter(type => !suggestedTypes.includes(type)).map(buttonHtml).join('');
  }

  function getPath(path) {
    return path.split('.').reduce((obj, key) => obj ? obj[key] : undefined, state.corporate);
  }

  function setPath(path, value) {
    const parts = path.split('.');
    let cursor = state.corporate;
    while (parts.length > 1) {
      const key = parts.shift();
      if (cursor[key] === undefined || cursor[key] === null) cursor[key] = /^\d+$/.test(parts[0]) ? [] : {};
      cursor = cursor[key];
    }
    cursor[parts[0]] = value;
  }

  function textInput(path, label, max, placeholder='') {
    const value = getPath(path) || '';
    return `<label>${esc(label)}${max ? ` <span>${String(value).length}/${max}</span>` : ''}<input data-corp-path="${esc(path)}" ${max ? `maxlength="${max}"` : ''} value="${esc(value)}" placeholder="${esc(placeholder)}"></label>`;
  }

  function areaInput(path, label, max, placeholder='') {
    const value = getPath(path) || '';
    return `<label>${esc(label)}${max ? ` <span>${String(value).length}/${max}</span>` : ''}<textarea data-corp-path="${esc(path)}" ${max ? `maxlength="${max}"` : ''} placeholder="${esc(placeholder)}">${esc(value)}</textarea></label>`;
  }

  function uploadInput(path, label, ratioClass) {
    const value = getPath(path) || '';
    return `<div class="corp-upload ${ratioClass || ''}"><div>${value ? `<img src="${esc(value)}" alt="">` : '<i class="fa-regular fa-image"></i>'}</div><label class="small-btn">${esc(label)}<input data-corp-file="${esc(path)}" type="file" accept="image/*" hidden></label></div>`;
  }

  function renderCorporateControls() {
    const wrap = $('#corporate-fields');
    const editor = $('#corporate-editor');
    if (!wrap || !editor) return;
    wrap.hidden = (state.page_type || 'creator') !== 'corporate';
    if (wrap.hidden) return;
    const c = state.corporate = mergeCorporate(state.corporate);
    const slotInputs = (key, count, label, max) => Array.from({ length: count }, (_, i) => textInput(`${key}.${i}`, `${label} ${i + 1}`, max)).join('');
    const linkInputs = Array.from({ length: 3 }, (_, i) => `<div class="corp-repeat-item"><strong>Link ${i + 1}</strong>${textInput(`links.${i}.label`, 'Label', 60)}${textInput(`links.${i}.url`, 'URL', 1200)}</div>`).join('');
    const socialInputs = Array.from({ length: 6 }, (_, i) => `<div class="corp-repeat-item"><strong>Social ${i + 1}</strong>${textInput(`socials.${i}.platform`, 'Platform', 40, 'LinkedIn')}${textInput(`socials.${i}.url`, 'URL', 1200)}</div>`).join('');
    const buttonInputs = (c.buttons || []).slice(0, 8).map((button, i) => `<div class="corp-repeat-item"><strong>Button ${i + 1}</strong>${textInput(`buttons.${i}.label`, 'Label', 30)}${textInput(`buttons.${i}.url`, 'URL', 1200)}${textInput(`buttons.${i}.button_color`, 'Button color', 7)}${textInput(`buttons.${i}.text_color`, 'Text color', 7)}</div>`).join('');
    const cardInputs = (c.cards || []).slice(0, 12).map((card, i) => `<div class="corp-repeat-item wide"><strong>Card ${i + 1}</strong><div class="form-grid">${textInput(`cards.${i}.title`, 'Title', 100)}<label>Type<select data-corp-path="cards.${i}.type"><option value="text" ${card.type === 'text' ? 'selected' : ''}>Text</option><option value="video" ${card.type === 'video' ? 'selected' : ''}>Video - YouTube/Vimeo only</option><option value="pdf" ${card.type === 'pdf' ? 'selected' : ''}>PDF link only</option></select></label>${textInput(`cards.${i}.link`, 'Required link', 1200)}${textInput(`cards.${i}.cta_label`, 'CTA label', 30)}${areaInput(`cards.${i}.description`, 'Description', 220)}<label>Fill<select data-corp-path="cards.${i}.fill_type"><option value="color" ${card.fill_type === 'color' ? 'selected' : ''}>Color</option><option value="gradient" ${card.fill_type === 'gradient' ? 'selected' : ''}>Gradient</option><option value="photo" ${card.fill_type === 'photo' ? 'selected' : ''}>Photo</option></select></label>${textInput(`cards.${i}.fill_color`, 'Fill color', 7)}${textInput(`cards.${i}.gradient_start`, 'Gradient start', 7)}${textInput(`cards.${i}.gradient_end`, 'Gradient end', 7)}${textInput(`cards.${i}.outline_color`, 'Outline color', 7)}<label>Outline weight<input data-corp-path="cards.${i}.outline_weight" type="number" min="0" max="5" value="${esc(card.outline_weight || 0)}"></label></div>${uploadInput(`cards.${i}.photo`, 'Upload card photo', 'wide-photo')}</div>`).join('');
    const memberInputs = (c.team.members || []).slice(0, 12).map((member, i) => `<div class="corp-repeat-item"><strong>Team member ${i + 1}</strong>${uploadInput(`team.members.${i}.photo`, 'Photo', 'square')}${textInput(`team.members.${i}.name`, 'Name', 120)}${textInput(`team.members.${i}.title`, 'Title', 30)}${textInput(`team.members.${i}.phone`, 'Phone', 40)}${textInput(`team.members.${i}.email`, 'Email', 120)}${textInput(`team.members.${i}.linkedin`, 'LinkedIn', 1200)}</div>`).join('');
    editor.innerHTML = `
      <div class="corp-editor-section"><h3>Identity</h3><div class="form-grid">${uploadInput('header_photo', 'Upload header photo', 'wide-photo')}${uploadInput('logo', 'Upload square logo', 'square')}${textInput('company_name', 'Company name', 100)}<label>Page ID<input value="${esc(state.slug || '')}" disabled></label>${areaInput('description', 'Description', 200)}</div></div>
      <div class="corp-editor-section"><h3>Section Titles</h3><div class="form-grid">${textInput('cards_title', 'Cards section title', 80)}${areaInput('cards_lede', 'Cards lede', 200)}${textInput('actions_title', 'Actions section title', 80)}${areaInput('actions_lede', 'Actions lede', 200)}${textInput('event_register_title', 'Event register title', 80)}</div></div>
      <div class="corp-editor-section"><h3>Hero & Action Buttons</h3><div class="form-grid">${textInput('hero_primary_cta_label', 'Primary CTA label', 60)}${textInput('hero_primary_cta_url', 'Primary CTA URL', 1200)}</div></div>
      <div class="corp-editor-section"><h3>Contact Section</h3><div class="form-grid">${textInput('contact.meeting_link', 'Book a Meeting link', 1200)}${textInput('contact.brochure_link', 'Download Brochure link', 1200)}${textInput('contact.phone', 'Phone', 40)}${textInput('contact.email', 'Email', 120)}${textInput('contact.whatsapp', 'WhatsApp phone', 40)}</div><p class="muted-row">Request Quote appears on the public page as a form with name, company, phone, email, and request fields.</p></div>
      <div class="corp-editor-section"><h3>Company details</h3><div class="form-grid">${textInput('company_website', 'Company website', 1200, 'https://example.com')}${textInput('locations.0', 'Primary location', 80, 'City, Country')}<label>Tags (comma-separated)<textarea data-corp-tags="specialties" placeholder="AI, SaaS, Industrial">${esc((c.specialties || []).join(', '))}</textarea></label></div><div class="corp-repeat-grid">${linkInputs}</div></div>
      <div class="corp-editor-section"><h3>Socials</h3><div class="corp-repeat-grid">${socialInputs}</div></div>
      <div class="corp-editor-section"><h3>Event Card</h3><div class="form-grid">${textInput('event.title', 'Title', 80)}${areaInput('event.description', 'Description', 150)}${textInput('event.start_at', 'Start date/time', 40)}${textInput('event.end_at', 'End date/time', 40)}${textInput('event.location', 'Location', 120)}${textInput('event.city', 'City', 80)}${textInput('event.book_link', 'Book meeting link', 1200)}${textInput('event.brochure_link', 'Event brochure link', 1200)}${textInput('event.card_color', 'Card color', 7)}${textInput('event.button_label', 'Button label', 40)}</div><label class="check-row"><input data-corp-path="event.countdown" type="checkbox" ${c.event.countdown ? 'checked' : ''}> Countdown on</label><label class="check-row"><input data-corp-path="event.register" type="checkbox" ${c.event.register ? 'checked' : ''}> Show register CTA</label></div>
      <div class="corp-editor-section"><h3>Buttons</h3><div class="corp-repeat-grid">${buttonInputs}</div><button class="small-btn" type="button" data-corp-add="buttons">Add button</button></div>
      <div class="corp-editor-section"><h3>Request Quote</h3><div class="form-grid">${textInput('quote_title', 'Title', 80)}${areaInput('quote_description', 'Description', 180)}${textInput('quote_button_label', 'Button label', 40)}</div></div>
      <div class="corp-editor-section"><h3>Card Area</h3><p class="muted-row">Use text, YouTube/Vimeo video links, or PDF links. One card spans the row; two cards split 2 columns and 1 column; three cards use equal columns.</p><div class="corp-repeat-grid">${cardInputs}</div><button class="small-btn" type="button" data-corp-add="cards">Add card</button></div>
      <div class="corp-editor-section"><h3>Team</h3><div class="form-grid">${textInput('team.title', 'Section title', 30)}${areaInput('team.description', 'Section description', 150)}</div><div class="corp-repeat-grid">${memberInputs}</div><button class="small-btn" type="button" data-corp-add="team.members">Add team member</button></div>`;
  }

  function renderAnalyticsPanel() {
    const panel = $('#analytics-panel');
    if (!panel) return;
    if (analyticsState.loading) {
      panel.innerHTML = '<div class="analytics-loading">Loading analytics…</div>';
      return;
    }
    if (analyticsState.error) {
      panel.innerHTML = `<div class="analytics-empty">${esc(analyticsState.error)}</div>`;
      return;
    }
    if (!analyticsState.data) {
      panel.innerHTML = '<div class="analytics-empty">No analytics data yet for this page.</div>';
      return;
    }
    const data = analyticsState.data;
    const trendData = Array.isArray(data.page_views_7_day_trend) ? data.page_views_7_day_trend : [];
    const maxTrend = Math.max(...trendData.map(item => item.count), 1);
    const trendPoints = trendData.map(item => `<div class="analytics-trend-bar" style="height:${Math.round((item.count / maxTrend) * 100)}%"><span>${esc(item.count)}</span><small>${esc(item.date.slice(5))}</small></div>`).join('');
    const topBlocksHtml = Array.isArray(data.top_blocks) && data.top_blocks.length > 0
      ? data.top_blocks.map(block => `<li><strong>${esc(block.title || 'Untitled')}</strong><span>${esc(block.clicks)} clicks</span><small>${esc(block.destination_url || block.type || '')}</small></li>`).join('')
      : '<li>No clicked links yet</li>';
    const hasActivity = (data.page_views || 0) > 0 || (data.link_clicks || 0) > 0 || (data.qr_scans || 0) > 0;
    const noTrafficNotice = !hasActivity ? '<div class="analytics-empty">No traffic has been recorded yet. Visitors will appear here once your page is viewed.</div>' : '';

    panel.innerHTML = `
      ${noTrafficNotice}
      <div class="analytics-summary-grid">
        <article class="analytics-card"><span class="label">Views</span><span class="value">${esc(data.page_views ?? 0)}</span><span class="hint">Total page visits</span></article>
        <article class="analytics-card"><span class="label">Visitors</span><span class="value">${esc(data.unique_visitors ?? 0)}</span><span class="hint">Distinct visitors</span></article>
        <article class="analytics-card"><span class="label">Link clicks</span><span class="value">${esc(data.link_clicks ?? 0)}</span><span class="hint">Clicks on page links</span></article>
        <article class="analytics-card"><span class="label">QR scans</span><span class="value">${esc(data.qr_scans ?? 0)}</span><span class="hint">Scans from QR codes</span></article>
      </div>
      <div class="analytics-card analytics-engagement-card"><span class="label">Engagement</span><span class="value">${esc(data.ctr_percent ?? 0)}%</span><span class="hint">Click-through rate from views</span></div>
      <div class="analytics-card analytics-trend-card">
        <span class="label">7-day trend</span>
        <div class="analytics-trend-chart">${trendPoints}</div>
        <span class="hint">Page views over the past week</span>
      </div>
      <div class="analytics-card analytics-top-list-card">
        <span class="label">Top clicked links</span>
        <ul class="analytics-top-list">${topBlocksHtml}</ul>
      </div>
    `;
  }

  async function loadAnalytics() {
    if (!state.id) return;
    if (analyticsState.pageId === state.id && analyticsState.data) {
      renderAnalyticsPanel();
      return;
    }
    analyticsState.loading = true;
    analyticsState.error = null;
    renderAnalyticsPanel();
    try {
      const response = await fetch(`api/pages.php?analytics=1&id=${state.id}`);
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.error || 'Unable to load analytics');
      analyticsState.data = data.analytics || null;
      analyticsState.pageId = state.id;
    } catch (error) {
      analyticsState.error = error.message || 'Unable to load analytics';
      analyticsState.data = null;
    } finally {
      analyticsState.loading = false;
      renderAnalyticsPanel();
    }
  }

  function rerender() { renderBlocks(); renderDesignControls(); renderCorporateControls(); renderPreview(); }

  function fileToDataUrl(file, cb) {
    if (!file || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = () => cb(reader.result);
    reader.readAsDataURL(file);
  }

  function addBlock(type) {
    state.blocks ||= [];
    const preset = blockTypes[type] || blockTypes.link;
    state.blocks.push({ id: uid(), type, title: preset.title || blockLabel(type), description: preset.description || '', destination_url: preset.destination || '', image_path: '', metadata: {}, is_active: true });
    markDirty(); rerender();
  }

  $$('.builder-tabs button').forEach(btn => btn.addEventListener('click', () => {
    $$('.builder-tabs button').forEach(b => b.classList.remove('active'));
    $$('.builder-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    const tab = $(`#tab-${btn.dataset.tab}`);
    if (tab) tab.classList.add('active');
    if (btn.dataset.tab === 'track') {
      loadAnalytics();
    }
  }));

  $('#add-block').addEventListener('click', () => { $('#block-type-menu').hidden = !$('#block-type-menu').hidden; });
  $('#block-type-menu').addEventListener('click', e => { const b = e.target.closest('button[data-type]'); if (!b) return; addBlock(b.dataset.type); $('#block-type-menu').hidden = true; });
  $('#show-more-blocks')?.addEventListener('click', () => {
    const more = $('#more-block-types');
    if (more) more.hidden = !more.hidden;
  });

  $('#block-list').addEventListener('input', e => {
    const card = e.target.closest('.builder-block'); if (!card) return;
    const block = state.blocks[+card.dataset.index]; if (!block) return;
    if (e.target.dataset.field && e.target.dataset.field !== 'image_file') { block[e.target.dataset.field] = e.target.value; markDirty(); renderPreview(); }
  });
  $('#block-list').addEventListener('change', e => {
    if (e.target.dataset.field === 'image_file') {
      const card = e.target.closest('.builder-block'); const block = state.blocks[+card.dataset.index];
      fileToDataUrl(e.target.files[0], data => { block.image_path = data; markDirty(); rerender(); });
    }
  });
  $('#block-list').addEventListener('click', e => {
    const action = e.target.closest('button[data-action]'); if (!action) return;
    const i = +action.closest('.builder-block').dataset.index;
    if (action.dataset.action === 'up' && i > 0) [state.blocks[i-1], state.blocks[i]] = [state.blocks[i], state.blocks[i-1]];
    if (action.dataset.action === 'down' && i < state.blocks.length - 1) [state.blocks[i+1], state.blocks[i]] = [state.blocks[i], state.blocks[i+1]];
    if (action.dataset.action === 'toggle') state.blocks[i].is_active = !state.blocks[i].is_active;
    if (action.dataset.action === 'delete') state.blocks.splice(i, 1);
    markDirty(); rerender();
  });

  document.addEventListener('input', e => {
    if (e.target.dataset.corpTags) {
      const tags = e.target.value.split(',').map(item => item.trim()).filter(Boolean);
      setPath(e.target.dataset.corpTags, tags);
      markDirty(); renderPreview();
      return;
    }
    if (e.target.dataset.corpPath) {
      setPath(e.target.dataset.corpPath, e.target.type === 'number' ? Number(e.target.value) : e.target.value);
      markDirty(); renderPreview();
      return;
    }
    const id = e.target.id;
    if (id === 'page-title') state.title = e.target.value.slice(0,32);
    else if (id === 'page-description') state.description = e.target.value.slice(0,80);
    else if (id === 'page-slug') state.slug = e.target.value.toLowerCase().replace(/[^a-z0-9_-]+/g,'-').replace(/^-+|-+$/g,'');
    else if (id === 'header-color') state.header.color = e.target.value;
    else if (id === 'header-gradient-start') state.header.gradient_start = e.target.value;
    else if (id === 'header-gradient-end') state.header.gradient_end = e.target.value;
    else if (id === 'background-color') state.background.color = e.target.value;
    else if (id === 'background-gradient-start') state.background.gradient_start = e.target.value;
    else if (id === 'background-gradient-end') state.background.gradient_end = e.target.value;
    else if (id === 'title-color') state.text_color = e.target.value;
    else if (id === 'description-color') state.description_color = e.target.value;
    else if (id === 'block-color') state.block_style.block_color = e.target.value;
    else if (id === 'block-text-color') state.block_style.block_text_color = e.target.value;
    else return;
    markDirty(); renderDesignControls(); renderPreview();
  });

  document.addEventListener('change', e => {
    if (e.target.dataset.corpFile) {
      fileToDataUrl(e.target.files[0], data => { setPath(e.target.dataset.corpFile, data); markDirty(); rerender(); });
      return;
    }
    if (e.target.dataset.corpPath) {
      setPath(e.target.dataset.corpPath, e.target.type === 'checkbox' ? e.target.checked : e.target.value);
      markDirty(); rerender();
      return;
    }
    if (e.target.id === 'font-select') state.font = e.target.value;
    else if (e.target.id === 'page-type-select') {
      const nextType = e.target.value === 'corporate' ? 'corporate' : 'creator';
      if (nextType === state.page_type) return;
      state.page_type = nextType;
    }
    else if (e.target.id === 'header-fit') state.header.fit = e.target.value;
    else if (e.target.id === 'hide-branding') state.branding.hide_xinng_logo = e.target.checked;
    else if (e.target.name === 'socialStyle') state.social_style = e.target.value;
    else if (e.target.name === 'socialPlacement') state.social_placement = e.target.value;
    else if (e.target.dataset.platform) {
      const p = e.target.dataset.platform;
      state.socials = (state.socials || []).filter(s => s.platform !== p);
      if (e.target.checked) state.socials.push({ platform:p, label:p, url:'', icon:p, is_active:true });
    } else if (e.target.dataset.platformUrl) {
      const s = (state.socials || []).find(item => item.platform === e.target.dataset.platformUrl);
      if (s) s.url = e.target.value;
    } else if (e.target.id === 'profile-image-input') fileToDataUrl(e.target.files[0], data => { state.profile_image = data; markDirty(); rerender(); return; });
    else if (e.target.id === 'header-image-input') fileToDataUrl(e.target.files[0], data => { state.header.image = data; state.header.mode = 'image'; markDirty(); rerender(); return; });
    else if (e.target.id === 'background-image-input') fileToDataUrl(e.target.files[0], data => { state.background.image = data; state.background.mode = 'image'; markDirty(); rerender(); return; });
    else return;
    markDirty(); rerender();
  });

  document.addEventListener('click', e => {
    const addCorp = e.target.closest('[data-corp-add]');
    if (addCorp) {
      const target = addCorp.dataset.corpAdd;
      if (target === 'cards' && state.corporate.cards.length < 12) state.corporate.cards.push({ ...defaultCorporate().cards[0], title:'' });
      if (target === 'buttons' && state.corporate.buttons.length < 8) state.corporate.buttons.push({ ...defaultCorporate().buttons[0] });
      if (target === 'team.members' && state.corporate.team.members.length < 12) state.corporate.team.members.push({ photo:'', name:'', title:'', phone:'', email:'', linkedin:'' });
      markDirty(); rerender(); return;
    }
    const theme = e.target.closest('[data-theme]');
    if (theme) {
      const t = themes[theme.dataset.theme]; state.theme = theme.dataset.theme; state.background.color = t.bg; state.header.color = t.header; state.text_color = t.text; state.description_color = t.desc; state.block_style.block_color = t.block; state.block_style.block_text_color = t.blockText; markDirty(); rerender(); return;
    }
    const layout = e.target.closest('[data-layout]');
    if (layout) { state.layout = layout.dataset.layout; markDirty(); rerender(); return; }
    const shape = e.target.closest('[data-shape]');
    if (shape) { state.block_style.shape = shape.dataset.shape; markDirty(); rerender(); return; }
    const shadow = e.target.closest('[data-shadow]');
    if (shadow) { state.block_style.shadow = shadow.dataset.shadow; markDirty(); rerender(); return; }
    const segment = e.target.closest('.segmented button');
    if (segment) { const [obj,key] = segment.closest('.segmented').dataset.bind.split('.'); state[obj][key] = segment.dataset.value; markDirty(); rerender(); return; }
    if (e.target.closest('#remove-profile-image')) { state.profile_image = ''; markDirty(); rerender(); }
  });

  $('#copy-page-url').addEventListener('click', async () => {
    const url = `${config.publicBase}/${state.slug}`;
    try { await navigator.clipboard.writeText(url); } catch(e) { console.error('Unable to copy page URL', e); }
  });

  $('#publish-page').addEventListener('click', async () => {
    const button = $('#publish-page');
    button.disabled = true; button.textContent = 'Publishing...';
    const response = await fetch('api/pages.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ _method:'PATCH', csrf_token: config.csrf, id: state.id, state })
    });
    const data = await response.json().catch(() => ({ ok:false, error:'Invalid response' }));
    if (data.ok) { dirty = false; button.textContent = 'Published'; $('#builder-url-text').textContent = data.public_url.replace(/^https?:\/\//,''); }
    else { button.disabled = false; button.textContent = 'Publish changes'; console.error(data.error || 'Unable to publish page.'); }
  });

  rerender();
})();
