/* script.js - 星沐手作 Brand Interactivity */

document.addEventListener('DOMContentLoaded', () => {

  // ==================== PRODUCT DATA ====================
  const PRODUCTS = [
    {
      id: 'prod-1',
      name: '恐龍樂園雙層拉鍊包',
      price: 390,
      image: 'assets/product1.jpg',
      desc: '經典黃色印花布搭配軟萌恐龍，雙層收納大空間。正面貼心透明膠片格層，附帶撞色手拎帶，實用又童趣。',
      popular: true
    },
    {
      id: 'prod-2',
      name: '萌萌熊貓棉花糖彈片包',
      price: 280,
      image: 'assets/product2.jpg',
      desc: '粉綠色調配上香甜棉花糖熊貓，搭配柔軟珊瑚粉口金翻蓋與撞色金屬按扣，輕壓即開，最適合收納零錢與耳機。',
      popular: true
    },
    {
      id: 'prod-3',
      name: '莫內花園手感捲軸杯套',
      price: 220,
      image: 'assets/product3.jpg',
      desc: '藍色夢幻水彩玫瑰如同印象派畫作，質地硬挺。收納時可如捲軸般輕巧捲起，側邊車縫精緻品牌布標。',
      popular: true
    },
    {
      id: 'prod-4',
      name: '莫內花園隨行提帶杯套',
      price: 250,
      image: 'assets/product4.jpg',
      desc: '莫內花園系列杯套完整展開版，附有專屬同花色加長調整型手提帶，提著走超方便，環保也能美美的！',
      popular: false
    }
  ];

  // ==================== SIMULATOR STATE ====================
  let simulatorState = {
    type: 'pouch', // pouch, flappouch, cupsleeve, babybib
    pattern: 'pat-dino', // pat-dino, pat-panda, pat-garden, pat-strawberry, pat-balloon
    accessories: [] // tag, strap
  };

  const TYPE_PRICES = {
    pouch: 390,
    flappouch: 280,
    cupsleeve: 220,
    babybib: 260
  };

  const ACCESSORY_PRICES = {
    tag: 20,
    strap: 40
  };

  const TYPE_NAMES = {
    pouch: '客製款-萬用拉鍊包',
    flappouch: '客製款-彈片口金包',
    cupsleeve: '客製款-隨行杯套',
    babybib: '客製款-寶寶口水巾'
  };

  const PATTERN_NAMES = {
    'pat-dino': '恐龍樂園',
    'pat-panda': '糖果熊貓',
    'pat-garden': '莫內花園',
    'pat-strawberry': '草莓派對',
    'pat-balloon': '小黃花氣球'
  };

  // ==================== SHOPPING CART STATE ====================
  let cart = [];

  // Load cart from LocalStorage
  if (localStorage.getItem('starmu_cart')) {
    try {
      cart = JSON.parse(localStorage.getItem('starmu_cart'));
    } catch (e) {
      cart = [];
    }
  }

  // ==================== DOM ELEMENTS ====================
  const navLinks = document.getElementById('nav-links');
  const pageSections = document.querySelectorAll('.page-section');
  const menuToggle = document.getElementById('menu-toggle');
  const cartToggle = document.getElementById('cart-toggle');
  const cartClose = document.getElementById('cart-close');
  const cartDrawer = document.getElementById('cart-drawer');
  const cartBackdrop = document.getElementById('cart-backdrop');
  const cartItemsContainer = document.getElementById('cart-items-container');
  const cartTotalDisplay = document.getElementById('cart-total-display');
  const cartCount = document.getElementById('cart-count');
  const toastContainer = document.getElementById('toast-container');
  const checkoutBtn = document.getElementById('btn-checkout');


  // ==================== INITIALIZATION ====================
  function init() {
    renderHotItems();
    renderCatalog();
    updateCartUI();
    renderSimulatorSVG();
    setupRouting();
    setupScrollAnimations();
  }

  // ==================== SPA ROUTING ====================
  function setupRouting() {
    // Tab switching event
    document.querySelectorAll('[data-tab], .btn-tab-trigger').forEach(trigger => {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        const targetTab = trigger.getAttribute('data-tab') || trigger.getAttribute('data-target');
        switchTab(targetTab);
        
        // Update URL hash
        window.location.hash = targetTab;
      });
    });

    // Handle back/forward navigation or initial load
    window.addEventListener('hashchange', () => {
      const hash = window.location.hash.substring(1);
      if (hash && document.getElementById(`page-${hash}`)) {
        switchTab(hash);
      }
    });

    // Check initial hash on load
    const initialHash = window.location.hash.substring(1);
    if (initialHash && document.getElementById(`page-${initialHash}`)) {
      switchTab(initialHash);
    } else {
      switchTab('home');
    }
  }

  function switchTab(tabId) {
    // Hide all sections, show target
    pageSections.forEach(section => {
      section.classList.remove('active');
    });
    const targetSection = document.getElementById(`page-${tabId}`);
    if (targetSection) {
      targetSection.classList.add('active');
    }

    // Update nav active styles
    document.querySelectorAll('.nav-links li').forEach(li => {
      li.classList.remove('active');
      const a = li.querySelector('a');
      if (a && a.getAttribute('data-tab') === tabId) {
        li.classList.add('active');
      }
    });

    // Close mobile menu if open
    navLinks.classList.remove('mobile-open');

    // Scroll to top of the page smoothly
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Trigger animation observer check
    setTimeout(setupScrollAnimations, 100);
  }

  // Mobile navigation drawer toggle
  menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('mobile-open');
  });

  // ==================== SCROLL ANIMATIONS ====================
  function setupScrollAnimations() {
    const fadeSections = document.querySelectorAll('.fade-in-section');
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          // Once visible, no need to keep observing
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.12,
      rootMargin: "0px 0px -50px 0px"
    });

    fadeSections.forEach(section => {
      // If section is already high on screen (like hero), show instantly
      const rect = section.getBoundingClientRect();
      if (rect.top < window.innerHeight) {
        section.classList.add('visible');
      } else {
        observer.observe(section);
      }
    });
  }

  // ==================== RENDER PRODUCTS ====================
  function renderHotItems() {
    const hotGrid = document.getElementById('home-hot-grid');
    if (!hotGrid) return;
    hotGrid.innerHTML = '';

    const popularProducts = PRODUCTS.filter(p => p.popular);
    popularProducts.forEach(product => {
      const card = createProductCard(product);
      hotGrid.appendChild(card);
    });
  }

  function renderCatalog() {
    const catalogGrid = document.getElementById('catalog-grid');
    if (!catalogGrid) return;
    catalogGrid.innerHTML = '';

    PRODUCTS.forEach(product => {
      const card = createProductCard(product);
      catalogGrid.appendChild(card);
    });
  }

  function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card fade-in-section';
    
    let badgeHtml = product.popular ? `<span class="badge-popular"><i class="fa-solid fa-heart"></i> 人氣推薦</span>` : '';
    
    card.innerHTML = `
      ${badgeHtml}
      <div class="product-img-wrapper">
        <img src="${product.image}" alt="${product.name}" class="product-card-img" loading="lazy">
      </div>
      <div class="product-info">
        <h3 class="product-title">${product.name}</h3>
        <p class="product-desc">${product.desc}</p>
        <div class="product-footer">
          <span class="product-price">${product.price}</span>
          <button class="btn-cute btn-add-cart btn-pink-add" data-id="${product.id}">
            加入購物車 <i class="fa-solid fa-basket-shopping"></i>
          </button>
        </div>
      </div>
    `;

    // Event listener for adding to cart
    card.querySelector('.btn-add-cart').addEventListener('click', (e) => {
      e.stopPropagation();
      addToCart(product.id);
    });

    return card;
  }

  // ==================== SHOPPING CART LOGIC ====================
  // Toggle cart drawer
  function toggleCart() {
    cartDrawer.classList.toggle('open');
    cartBackdrop.classList.toggle('open');
  }

  cartToggle.addEventListener('click', toggleCart);
  cartClose.addEventListener('click', toggleCart);
  cartBackdrop.addEventListener('click', toggleCart);

  // Add standard product to cart
  function addToCart(productId) {
    const product = PRODUCTS.find(p => p.id === productId);
    if (!product) return;

    const existingItem = cart.find(item => item.id === productId && !item.isCustom);
    if (existingItem) {
      existingItem.qty += 1;
    } else {
      cart.push({
        id: product.id,
        name: product.name,
        price: product.price,
        image: product.image,
        qty: 1,
        isCustom: false
      });
    }

    saveCart();
    updateCartUI();
    showToast(`已加入購物籃：${product.name} 🧺`);
  }

  // Save cart to storage
  function saveCart() {
    localStorage.setItem('starmu_cart', JSON.stringify(cart));
  }

  // Update whole cart UI
  function updateCartUI() {
    // Badge counter
    const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
    cartCount.textContent = totalQty;

    if (!cartItemsContainer) return;
    cartItemsContainer.innerHTML = '';

    if (cart.length === 0) {
      cartItemsContainer.innerHTML = `
        <div class="cart-empty-state">
          <span class="cart-empty-icon">🧺</span>
          <p>購物籃是空的喔～</p>
          <p style="font-size:0.85rem;">趕快去逛逛熱銷商品，或是體驗有趣的客製模擬吧！</p>
        </div>
      `;
      cartTotalDisplay.textContent = 'NT$ 0';
      return;
    }

    let totalPrice = 0;

    cart.forEach((item, index) => {
      const row = document.createElement('div');
      row.className = 'cart-item-row';
      
      const itemSubtotal = item.price * item.qty;
      totalPrice += itemSubtotal;

      let customDetailsHtml = item.isCustom ? 
        `<span style="font-size:0.75rem; color:var(--text-light); margin-top:2px;">
          花色：${item.customPatternName} <br> 
          配件：${item.customAccList.join(', ') || '無'}
        </span>` : '';

      // Set image source. Custom items use custom placeholders.
      let imgUrl = item.image;
      if (item.isCustom) {
        // Draw matching pattern placeholder based on custom item
        imgUrl = 'assets/logo.png'; // Fallback
      }

      row.innerHTML = `
        <img src="${imgUrl}" alt="${item.name}" class="cart-item-img">
        <div class="cart-item-info">
          <span class="cart-item-title" title="${item.name}">${item.name}</span>
          ${customDetailsHtml}
          <span class="cart-item-price">NT$ ${item.price}</span>
          <div class="cart-item-qty">
            <button class="qty-btn btn-minus" data-idx="${index}">-</button>
            <span class="qty-val">${item.qty}</span>
            <button class="qty-btn btn-plus" data-idx="${index}">+</button>
          </div>
        </div>
        <button class="cart-item-remove" data-idx="${index}"><i class="fa-solid fa-trash-can"></i></button>
      `;

      // Event listeners for item modifications
      row.querySelector('.btn-minus').addEventListener('click', () => changeQty(index, -1));
      row.querySelector('.btn-plus').addEventListener('click', () => changeQty(index, 1));
      row.querySelector('.cart-item-remove').addEventListener('click', () => removeFromCart(index));

      cartItemsContainer.appendChild(row);
    });

    cartTotalDisplay.textContent = `NT$ ${totalPrice}`;
  }

  function changeQty(index, delta) {
    cart[index].qty += delta;
    if (cart[index].qty <= 0) {
      cart.splice(index, 1);
    }
    saveCart();
    updateCartUI();
  }

  function removeFromCart(index) {
    const name = cart[index].name;
    cart.splice(index, 1);
    saveCart();
    updateCartUI();
    showToast(`已從購物籃移除：${name} 🗑️`);
  }

  // Toast notification
  function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `
      <span class="toast-icon">✨</span>
      <span>${message}</span>
    `;

    toastContainer.appendChild(toast);

    // Auto remove toast
    setTimeout(() => {
      toast.classList.add('removing');
      toast.addEventListener('animationend', () => {
        toast.remove();
      });
    }, 3000);
  }

  // Checkout alert simulation
  checkoutBtn.addEventListener('click', () => {
    if (cart.length === 0) return;
    
    alert('🎉 感謝您的訂購模擬！\n\n「星沐手作」為展示網站，此步驟代表訂購流程已成功串接！後續可透過官方 LINE 傳送您的訂單與規格喔～ (❁´◡`❁)');
    cart = [];
    saveCart();
    updateCartUI();
    toggleCart();
  });

  // Contact form submission response
  const contactForm = document.getElementById('contact-form');
  if (contactForm) {
    contactForm.addEventListener('submit', () => {
      const name = document.getElementById('contact-name').value;
      alert(`💌 哈囉 ${name}！\n\n我們已經收到您的信件囉！小恐龍正細心閱讀中，將會以最快的速度透過電子信箱與您回信聯繫，祝您有美好的一天！✨`);
      contactForm.reset();
    });
  }

  // ==================== PRODUCT CUSTOM SIMULATOR ====================
  const typeSelector = document.getElementById('type-selector');
  const patternSelector = document.getElementById('pattern-selector');
  const accessorySelector = document.getElementById('accessory-selector');
  const simPriceDisplay = document.getElementById('sim-price-display');
  const btnAddCustomCart = document.getElementById('btn-add-custom-cart');

  if (typeSelector && patternSelector && accessorySelector) {
    
    // 1. Type selection
    typeSelector.querySelectorAll('[data-type]').forEach(btn => {
      btn.addEventListener('click', () => {
        typeSelector.querySelectorAll('[data-type]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        simulatorState.type = btn.getAttribute('data-type');
        
        // Adapt accessories availability (e.g. bib doesn't require strap, let's keep it customizable but toggle check)
        updatePriceAndSimulator();
      });
    });

    // 2. Pattern selection
    patternSelector.querySelectorAll('[data-pattern]').forEach(btn => {
      btn.addEventListener('click', () => {
        patternSelector.querySelectorAll('[data-pattern]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        simulatorState.pattern = btn.getAttribute('data-pattern');
        updatePriceAndSimulator();
      });
    });

    // 3. Accessory selection (multi-select)
    accessorySelector.querySelectorAll('[data-acc]').forEach(btn => {
      btn.addEventListener('click', () => {
        const acc = btn.getAttribute('data-acc');
        const idx = simulatorState.accessories.indexOf(acc);
        
        if (idx > -1) {
          simulatorState.accessories.splice(idx, 1);
          btn.classList.remove('active');
          btn.querySelector('i').className = 'fa-regular fa-square-plus';
        } else {
          simulatorState.accessories.push(acc);
          btn.classList.add('active');
          btn.querySelector('i').className = 'fa-solid fa-square-check';
        }
        updatePriceAndSimulator();
      });
    });

    // 4. Add custom product to cart
    btnAddCustomCart.addEventListener('click', () => {
      // Calculate custom price
      const baseCost = TYPE_PRICES[simulatorState.type];
      const accCost = simulatorState.accessories.reduce((sum, acc) => sum + ACCESSORY_PRICES[acc], 0);
      const finalPrice = baseCost + accCost;

      const accNames = simulatorState.accessories.map(acc => {
        return acc === 'tag' ? '星沐布標' : '便攜提手帶';
      });

      const patternName = PATTERN_NAMES[simulatorState.pattern];
      const typeName = TYPE_NAMES[simulatorState.type];

      // Use logo as placeholder or special SVG icon
      const customItem = {
        id: `custom-${Date.now()}`,
        name: `${typeName} (${patternName})`,
        price: finalPrice,
        image: 'assets/logo.png', // Custom marker
        qty: 1,
        isCustom: true,
        customPatternName: patternName,
        customAccList: accNames
      };

      cart.push(customItem);
      saveCart();
      updateCartUI();
      showToast(`已加入購物籃：${customItem.name} 🎨`);
      
      // Auto open cart drawer
      setTimeout(toggleCart, 300);
    });
  }

  function updatePriceAndSimulator() {
    // Calculate total price
    const baseCost = TYPE_PRICES[simulatorState.type];
    const accCost = simulatorState.accessories.reduce((sum, acc) => sum + ACCESSORY_PRICES[acc], 0);
    const finalPrice = baseCost + accCost;

    simPriceDisplay.textContent = `NT$ ${finalPrice}`;
    renderSimulatorSVG();
  }

  // Draw dynamic premium SVGs
  function renderSimulatorSVG() {
    const svgGroup = document.getElementById('svg-product-group');
    if (!svgGroup) return;

    svgGroup.innerHTML = '';
    const patternId = simulatorState.pattern;
    const hasTag = simulatorState.accessories.includes('tag');
    const hasStrap = simulatorState.accessories.includes('strap');

    let svgContent = '';

    if (simulatorState.type === 'pouch') {
      // ---萬用拉鍊包 (Zip Pouch)---
      svgContent = `
        <!-- Optional Wrist Strap -->
        ${hasStrap ? `
          <path d="M 85,290 C 45,295 15,330 20,360 C 25,380 45,385 70,370 C 80,360 80,335 75,310" fill="none" stroke="#FCD299" stroke-width="15" stroke-linecap="round" />
          <path d="M 85,290 C 45,295 15,330 20,360 C 25,380 45,385 70,370 C 80,360 80,335 75,310" fill="none" stroke="url(#${patternId})" stroke-width="9" stroke-linecap="round" />
          <rect x="74" y="278" width="16" height="24" rx="4" transform="rotate(-15 82 290)" fill="#5C4E4B" />
          <circle cx="40" cy="365" r="5" fill="#F7C887" stroke="#5C4E4B" stroke-width="2" />
        ` : ''}
        
        <!-- Pouch Outer Frame -->
        <rect x="80" y="80" width="240" height="240" rx="24" fill="url(#${patternId})" stroke="#5C4E4B" stroke-width="5.5" />
        
        <!-- Stitching Lines -->
        <rect x="86" y="86" width="228" height="228" rx="18" fill="none" stroke="#FFF" stroke-dasharray="6,4" stroke-width="2.5" opacity="0.8" />
        
        <!-- Zipper Tape and Teeth -->
        <rect x="80" y="112" width="240" height="14" fill="#EAEAEA" stroke="#5C4E4B" stroke-width="2.5" />
        <line x1="80" y1="119" x2="320" y2="119" stroke="#5C4E4B" stroke-width="4" stroke-dasharray="3,3" />
        <line x1="80" y1="112" x2="320" y2="112" stroke="#5C4E4B" stroke-width="2.5" />
        <line x1="80" y1="126" x2="320" y2="126" stroke="#5C4E4B" stroke-width="2.5" />
        
        <!-- Zipper Slider -->
        <rect x="110" y="104" width="22" height="30" rx="4" fill="#FCD299" stroke="#5C4E4B" stroke-width="2.5" />
        <rect x="116" y="126" width="10" height="18" rx="2" fill="#5C4E4B" />

        <!-- Transparent Front Pocket Slot -->
        <path d="M 80,180 L 320,180 L 320,296 C 320,309 309,320 296,320 L 104,320 C 91,320 80,309 80,296 Z" fill="#FFF" fill-opacity="0.28" stroke="#5C4E4B" stroke-width="4.5" />
        <line x1="80" y1="180" x2="320" y2="180" stroke="#5C4E4B" stroke-width="4.5" />
        
        <!-- Pocket Snap Tab -->
        <rect x="180" y="165" width="40" height="30" rx="6" fill="#FFF" stroke="#5C4E4B" stroke-width="2.5" />
        <circle cx="200" cy="180" r="7" fill="#FFA3A3" stroke="#5C4E4B" stroke-width="2.5" />

        <!-- Optional Leather Brand Tag -->
        ${hasTag ? `
          <rect x="235" y="240" width="55" height="22" rx="4" fill="#D6C7C2" stroke="#5C4E4B" stroke-width="2.5" />
          <text x="262" y="254" font-family="Comfortaa" font-weight="bold" font-size="8.5" text-anchor="middle" fill="#5C4E4B">★Starmu</text>
        ` : ''}
      `;
    } 
    else if (simulatorState.type === 'flappouch') {
      // ---彈片口金包 (Flap Pouch)---
      svgContent = `
        <!-- Optional Wrist Strap -->
        ${hasStrap ? `
          <path d="M 290,150 C 330,160 360,200 355,240 C 350,270 320,290 300,260" fill="none" stroke="#FCD299" stroke-width="12" stroke-linecap="round" />
          <path d="M 290,150 C 330,160 360,200 355,240 C 350,270 320,290 300,260" fill="none" stroke="url(#${patternId})" stroke-width="7" stroke-linecap="round" />
          <circle cx="340" cy="245" r="4.5" fill="#5C4E4B" />
        ` : ''}

        <!-- Pouch Trapezoid Body -->
        <path d="M 110,135 Q 85,260 110,315 C 120,335 280,335 290,315 Q 315,260 290,135 Z" fill="url(#${patternId})" stroke="#5C4E4B" stroke-width="5.5" />
        
        <!-- Stitching -->
        <path d="M 116,142 Q 93,258 116,309 C 124,325 276,325 284,309 Q 307,258 284,142" fill="none" stroke="#FFF" stroke-width="2.5" stroke-dasharray="6,4" opacity="0.8" />
        
        <!-- Gathering Fold Lines -->
        <path d="M 150,305 Q 155,325 160,325" fill="none" stroke="#5C4E4B" stroke-width="2.5" stroke-linecap="round" />
        <path d="M 250,305 Q 245,325 240,325" fill="none" stroke="#5C4E4B" stroke-width="2.5" stroke-linecap="round" />
        <path d="M 200,310 L 200,329" fill="none" stroke="#5C4E4B" stroke-width="2.5" stroke-linecap="round" />

        <!-- Cute Flap (Contrast Pink) -->
        <path d="M 130,135 L 270,135 L 250,210 C 230,235 170,235 150,210 Z" fill="#FFA3A3" stroke="#5C4E4B" stroke-width="4.5" />
        <path d="M 138,141 L 262,141 L 244,204 C 226,224 174,224 156,204" fill="none" stroke="#FFF" stroke-width="2" stroke-dasharray="4,3" opacity="0.8" />
        
        <!-- Flap Snap Button -->
        <circle cx="200" cy="192" r="11" fill="#97C09E" stroke="#5C4E4B" stroke-width="2.5" />
        <circle cx="200" cy="192" r="4" fill="#FFF" fill-opacity="0.4" />

        <!-- Optional Seam Tag -->
        ${hasTag ? `
          <rect x="80" y="245" width="22" height="32" rx="3" fill="#D6C7C2" stroke="#5C4E4B" stroke-width="2.5" />
          <text x="91" y="265" font-family="Comfortaa" font-weight="bold" font-size="8" transform="rotate(-90 91 265)" text-anchor="middle" fill="#5C4E4B">STARMU</text>
        ` : ''}
      `;
    }
    else if (simulatorState.type === 'cupsleeve') {
      // ---隨行杯套 (Cup Sleeve)---
      svgContent = `
        <!-- Sleeve Handle Strap -->
        <path d="M 120,165 L 120,80 C 120,55 280,55 280,80 L 280,165" fill="none" stroke="#FCD299" stroke-width="18" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M 120,165 L 120,80 C 120,55 280,55 280,80 L 280,165" fill="none" stroke="url(#${patternId})" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M 114,165 L 114,80 C 114,50 286,50 286,80 L 286,165" fill="none" stroke="#5C4E4B" stroke-width="1.5" stroke-dasharray="4,3" />
        
        <!-- Metal Adjustment Ring -->
        <rect x="188" y="46" width="24" height="14" rx="2" fill="#D6C7C2" stroke="#5C4E4B" stroke-width="2.5" />

        <!-- Optional Hanging Strap Extension (if Strap accessory activated) -->
        ${hasStrap ? `
          <path d="M 120,70 L 80,140" fill="none" stroke="#F7C887" stroke-width="8" stroke-linecap="round" />
          <circle cx="80" cy="140" r="4.5" fill="#5C4E4B" />
        ` : ''}

        <!-- Cup Sleeve Main Curved Body -->
        <path d="M 90,160 L 310,160 C 295,245 280,265 280,275 L 120,275 C 120,265 105,245 90,160 Z" fill="url(#${patternId})" stroke="#5C4E4B" stroke-width="5.5" />
        
        <!-- Stitching Lines -->
        <path d="M 98,168 L 302,168 C 288,242 274,262 274,267 L 126,267 C 126,262 112,242 98,168" fill="none" stroke="#FFF" stroke-width="2.5" stroke-dasharray="5,4" opacity="0.8" />

        <!-- Strap-to-Sleeve Connection Snap Buttons -->
        <circle cx="120" cy="180" r="7" fill="#FFA3A3" stroke="#5C4E4B" stroke-width="2.5" />
        <circle cx="280" cy="180" r="7" fill="#FFA3A3" stroke="#5C4E4B" stroke-width="2.5" />

        <!-- Optional Brand Tag -->
        ${hasTag ? `
          <rect x="268" y="195" width="32" height="20" rx="3.5" fill="#D6C7C2" stroke="#5C4E4B" stroke-width="2" />
          <text x="284" y="207" font-family="Comfortaa" font-weight="bold" font-size="7" text-anchor="middle" fill="#5C4E4B">★SM</text>
        ` : ''}
      `;
    }
    else if (simulatorState.type === 'babybib') {
      // ---寶寶口水巾 (Baby Bib)---
      svgContent = `
        <!-- Bib Outer Body Shape -->
        <path d="M 160,80 C 130,80 100,100 100,145 C 100,225 120,335 200,335 C 280,335 300,225 300,145 C 300,100 270,80 240,80 C 220,80 210,100 200,100 C 190,100 180,80 160,80 Z" fill="url(#${patternId})" stroke="#5C4E4B" stroke-width="5.5" />
        
        <!-- Inner Cutout for baby neck (Fills white to cover main shape) -->
        <path d="M 160,80 C 180,80 190,100 200,100 C 210,100 220,80 240,80 C 255,80 270,95 270,120 C 270,160 250,210 200,210 C 150,210 130,160 130,120 C 130,95 145,80 160,80 Z" fill="#FFF" stroke="#5C4E4B" stroke-width="5.5" />
        
        <!-- Stitches -->
        <path d="M 160,88 C 135,88 108,106 108,145 C 108,219 126,327 200,327 C 274,327 292,219 292,145 C 292,106 265,88 240,88 C 224,88 214,106 200,106 C 186,106 176,88 160,88 Z" fill="none" stroke="#FFF" stroke-width="2" stroke-dasharray="5,4" opacity="0.8" />
        <path d="M 160,72 C 176,72 186,94 200,94 C 214,94 224,72 240,72" fill="none" stroke="#FFF" stroke-width="2" stroke-dasharray="4,3" opacity="0.8" />

        <!-- Shoulder Snap Button -->
        <circle cx="253" cy="98" r="8" fill="#F7C887" stroke="#5C4E4B" stroke-width="2" />
        <circle cx="253" cy="98" r="4" fill="none" stroke="#5C4E4B" stroke-width="1.5" stroke-dasharray="2,2" />

        <!-- Optional Bow Ribbon Decoration (represents strap styling) -->
        ${hasStrap ? `
          <path d="M 200,250 Q 185,235 170,250 Q 185,265 200,250 Q 215,235 230,250 Q 215,265 200,250" fill="#FFA3A3" stroke="#5C4E4B" stroke-width="2.5" />
          <circle cx="200" cy="250" r="5" fill="#97C09E" stroke="#5C4E4B" stroke-width="2" />
        ` : ''}

        <!-- Optional Brand Leather Tag -->
        ${hasTag ? `
          <rect x="175" y="275" width="50" height="20" rx="3.5" fill="#D6C7C2" stroke="#5C4E4B" stroke-width="2" />
          <text x="200" y="287" font-family="Comfortaa" font-weight="bold" font-size="8" text-anchor="middle" fill="#5C4E4B">★Starmu</text>
        ` : ''}
      `;
    }

    svgGroup.innerHTML = svgContent;
  }

  // ==================== RUN APPLICATION ====================
  init();
});
