document.addEventListener('DOMContentLoaded', function() {
    // Featured Products Carousel
    const featuredProductsTrack = document.getElementById('featured-products-track');
    const prevBtn = document.querySelector('.prev-featured');
    const nextBtn = document.querySelector('.next-featured');
    const paginationContainer = document.querySelector('.featured-pagination');
    
    let currentSlide = 0;
    let slidesPerView = calculateSlidesPerView();
    let totalSlides = 0;
    let products = [];
    
    // Load featured products
    loadFeaturedProducts();
    
    // Handle window resize
    window.addEventListener('resize', function() {
        slidesPerView = calculateSlidesPerView();
        updateCarousel();
    });
    
    // Navigation buttons
    prevBtn.addEventListener('click', function() {
        if (currentSlide > 0) {
            currentSlide--;
            updateCarousel();
        }
    });
    
    nextBtn.addEventListener('click', function() {
        if (currentSlide < totalSlides - slidesPerView) {
            currentSlide++;
            updateCarousel();
        }
    });
    
    function calculateSlidesPerView() {
        const width = window.innerWidth;
        if (width >= 1200) return 4;
        if (width >= 992) return 3;
        if (width >= 768) return 2;
        return 1;
    }
    
    function loadFeaturedProducts() {
        // In a real application, you would fetch this from your API
        // For demo purposes, we'll use mock data
        products = [
            {
                id: 1,
                name: "Premium Wireless Headphones",
                price: 129.99,
                oldPrice: 159.99,
                image: "headphone.webp",
                rating: 4.5,
                reviewCount: 128,
                badge: "Bestseller",
                department: "electronics"
            },
            {
                id: 2,
                name: "Modern Design Book: Latest Trends",
                price: 24.99,
                image: "designbook.webp",
                rating: 4.2,
                reviewCount: 56,
                badge: "New",
                department: "bookshop"
            },
            {
                id: 3,
                name: "Stainless Steel Water Bottle",
                price: 19.99,
                oldPrice: 24.99,
                image: "bottle.webp",
                rating: 4.7,
                reviewCount: 89,
                department: "mall"
            },
            {
                id: 4,
                name: "Smart Watch with Fitness Tracker",
                price: 89.99,
                image: "smartwatch.webp",
                rating: 4.3,
                reviewCount: 204,
                badge: "Sale",
                department: "electronics"
            },
            {
                id: 5,
                name: "Organic Cotton Throw Pillow",
                price: 29.99,
                image: "pillow.webp",
                rating: 4.1,
                reviewCount: 42,
                department: "mall"
            },
            {
                id: 6,
                name: "Programming Fundamentals E-book",
                price: 12.99,
                image: "probook.jpg",
                rating: 4.8,
                reviewCount: 156,
                department: "bookshop"
            },
            {
                id: 7,
                name: "Bluetooth Portable Speaker",
                price: 59.99,
                oldPrice: 79.99,
                image: "bluetooth.webp",
                rating: 4.4,
                reviewCount: 97,
                badge: "Limited",
                department: "electronics"
            },
            {
                id: 8,
                name: "Leather Journal Notebook",
                price: 22.99,
                image: "nootbook.webp",
                rating: 4.6,
                reviewCount: 73,
                department: "bookshop"
            }
        ];
        
        renderProducts();
        createPagination();
        updateCarousel();
    }
    
    function renderProducts() {
        featuredProductsTrack.innerHTML = '';
        
        products.forEach(product => {
            const productHTML = `
                <div class="featured-product" data-department="${product.department}">
                    <div class="featured-product-image">
                        <img src="${product.image}" alt="${product.name}">
                        ${product.badge ? `<span class="featured-product-badge">${product.badge}</span>` : ''}
                    </div>
                    <div class="featured-product-info">
                        <h3 class="featured-product-title">${product.name}</h3>
                        <div class="featured-product-price">
                            $${product.price.toFixed(2)}
                            ${product.oldPrice ? `<span class="old-price">$${product.oldPrice.toFixed(2)}</span>` : ''}
                        </div>
                        <div class="featured-product-rating">
                            ${generateStarRating(product.rating)} (${product.reviewCount})
                        </div>
                        <div class="featured-product-actions">
                            <button class="btn add-to-cart" data-product-id="${product.id}">Add to Cart</button>
                            <button class="btn wishlist"><i class="far fa-heart"></i></button>
                        </div>
                    </div>
                </div>
            `;
            
            featuredProductsTrack.insertAdjacentHTML('beforeend', productHTML);
        });
        
        totalSlides = products.length;
    }
    
    function generateStarRating(rating) {
        const fullStars = Math.floor(rating);
        const halfStar = rating % 1 >= 0.5 ? 1 : 0;
        const emptyStars = 5 - fullStars - halfStar;
        
        return '★'.repeat(fullStars) + (halfStar ? '½' : '') + '☆'.repeat(emptyStars);
    }
    
    function createPagination() {
        paginationContainer.innerHTML = '';
        const dotCount = Math.ceil(products.length / slidesPerView);
        
        for (let i = 0; i < dotCount; i++) {
            const dot = document.createElement('div');
            dot.className = 'pagination-dot';
            if (i === 0) dot.classList.add('active');
            dot.addEventListener('click', () => {
                currentSlide = i * slidesPerView;
                updateCarousel();
            });
            paginationContainer.appendChild(dot);
        }
    }
    
    function updateCarousel() {
        const slideWidth = 100 / slidesPerView;
        const translateX = -currentSlide * slideWidth;
        featuredProductsTrack.style.transform = `translateX(${translateX}%)`;
        
        // Update pagination dots
        const dots = document.querySelectorAll('.pagination-dot');
        const activeDotIndex = Math.floor(currentSlide / slidesPerView);
        
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === activeDotIndex);
        });
        
        // Update button states
        prevBtn.disabled = currentSlide === 0;
        nextBtn.disabled = currentSlide >= totalSlides - slidesPerView;
    }
    
    // Add to cart functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-to-cart')) {
            const productId = e.target.closest('.add-to-cart').dataset.productId;
            addToCart(productId);
        }
    });
    
    function addToCart(productId) {
        // In a real app, this would be an API call
        console.log(`Added product ${productId} to cart`);
        
        // Show notification
        showNotification('Product added to cart!');
        
        // Update cart count
        updateCartCount();
    }
    
    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }, 100);
    }
    
    function updateCartCount() {
        const cartCountElements = document.querySelectorAll('.cart-count');
        // In a real app, you would get the actual count from your cart
        const count = Math.floor(Math.random() * 5) + 1; // Random for demo
        cartCountElements.forEach(el => el.textContent = count);
    }
});

// Create product card HTML
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.dataset.id = product.product_id;
    
    const badge = product.is_featured ? '<div class="product-badge">Featured</div>' : '';
    const oldPrice = product.old_price ? `<span class="old-price">$${product.old_price}</span>` : '';
    
    card.innerHTML = `
        ${badge}
        <div class="product-image">
            <div class="placeholder-image ${product.department}-placeholder"></div>
            <img src="${product.image_url || 'images/placeholder.jpg'}" alt="${product.name}" style="display: none;">
        </div>
        <div class="product-info">
            <h3>${product.name}</h3>
            <div class="product-price">$${product.price} ${oldPrice}</div>
            <div class="product-rating">${generateStarRating(product.rating)} <span class="review-count">(${product.review_count || 0})</span></div>
            <div class="product-actions">
                <button class="btn add-to-cart">Add to Cart</button>
                <button class="btn wishlist"><i class="far fa-heart"></i></button>
            </div>
        </div>
    `;
    
    // Add image loading functionality
    const img = card.querySelector('img');
    const placeholder = card.querySelector('.placeholder-image');
    
    img.onload = function() {
        img.style.display = 'block';
        placeholder.style.display = 'none';
    };
    
    if (img.src) img.src = img.src; // Trigger loading
    
    return card;
}

// Generate star rating HTML
function generateStarRating(rating) {
    rating = rating || 0;
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5 ? 1 : 0;
    const emptyStars = 5 - fullStars - halfStar;
    
    return '★'.repeat(fullStars) + (halfStar ? '½' : '') + '☆'.repeat(emptyStars);
}

// Tab switching functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const department = this.dataset.department;
            loadFeaturedProducts(department === 'all' ? '' : department);
        });
    });
    // Add event delegation for add to cart buttons
document.addEventListener('click', function(e) {
    if (e.target.closest('.add-to-cart')) {
        const productCard = e.target.closest('.product-card');
        const productId = productCard.dataset.id;
        
        addToCart(productId, 1);
    }
});

function addToCart(productId, quantity) {
    fetch('api/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_item_count);
            showNotification('Product added to cart!');
        } else {
            showNotification('Failed to add to cart: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while adding to cart', 'error');
    });
}

function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(el => el.textContent = count);
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
    // Load initial featured products
    loadFeaturedProducts();
});