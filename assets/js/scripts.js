import { Chart } from "@/components/ui/chart"
// DOM Elements
document.addEventListener("DOMContentLoaded", () => {
  // Theme Toggle
  const themeToggle = document.getElementById("theme-toggle")
  const themeIcon = themeToggle ? themeToggle.querySelector("i") : null

  // Initialize theme from localStorage or system preference
  initializeTheme()

  // Add event listeners
  if (themeToggle) {
    themeToggle.addEventListener("click", toggleTheme)
  }

  // Initialize tooltips and popovers if Bootstrap is available
  if (typeof bootstrap !== "undefined") {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl))

    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    popoverTriggerList.map((popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl))
  }

  // Add to cart functionality
  initializeAddToCart()

  // Delete product functionality
  initializeDeleteButtons()

  // QR Code generation
  initializeQRCodeButtons()

  // Dashboard widgets
  initializeDashboardWidgets()

  // Chart initialization
  initializeCharts()

  // Scanner functionality
  initializeScanner()

  // Weather-based recommendations
  getWeatherRecommendations()
})

// Theme Functions
function initializeTheme() {
  const savedTheme = localStorage.getItem("theme") || "light"
  document.documentElement.setAttribute("data-theme", savedTheme)

  const themeToggle = document.getElementById("theme-toggle")
  if (themeToggle) {
    const themeIcon = themeToggle.querySelector("i")
    if (themeIcon) {
      themeIcon.className = savedTheme === "dark" ? "fas fa-sun" : "fas fa-moon"
    }
  }
}

function toggleTheme() {
  const currentTheme = document.documentElement.getAttribute("data-theme") || "light"
  const newTheme = currentTheme === "light" ? "dark" : "light"

  document.documentElement.setAttribute("data-theme", newTheme)
  localStorage.setItem("theme", newTheme)

  const themeIcon = this.querySelector("i")
  if (themeIcon) {
    themeIcon.className = newTheme === "dark" ? "fas fa-sun" : "fas fa-moon"
  }

  // Save theme preference to server if user is logged in
  if (typeof userId !== "undefined") {
    fetch("save_preferences.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `theme=${newTheme}&user_id=${userId}`,
    })
  }
}

// Add to cart functionality
function initializeAddToCart() {
  const addToCartButtons = document.querySelectorAll(".add-to-cart")
  addToCartButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault()
      const productId = this.getAttribute("data-id")
      const productName = this.getAttribute("data-name")

      // Create a form to submit
      const form = document.createElement("form")
      form.method = "POST"
      form.action = "cart.php"

      // Create product ID input
      const productIdInput = document.createElement("input")
      productIdInput.type = "hidden"
      productIdInput.name = "product_id"
      productIdInput.value = productId
      form.appendChild(productIdInput)

      // Create quantity input (default to 1)
      const quantityInput = document.createElement("input")
      quantityInput.type = "hidden"
      quantityInput.name = "quantity"
      quantityInput.value = "1"
      form.appendChild(quantityInput)

      // Create add to cart input
      const addToCartInput = document.createElement("input")
      addToCartInput.type = "hidden"
      addToCartInput.name = "add_to_cart"
      addToCartInput.value = "1"
      form.appendChild(addToCartInput)

      // Append form to body and submit
      document.body.appendChild(form)
      form.submit()

      // Show toast notification
      showToast(`${productName} added to cart!`, "success")
    })
  })
}

// Delete product functionality
function initializeDeleteButtons() {
  const deleteButtons = document.querySelectorAll(".delete-btn")
  deleteButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault()
      const productId = this.getAttribute("data-id")
      const productName = this.getAttribute("data-name")

      // Set the product name in the confirmation modal
      const deleteProductNameEl = document.getElementById("delete_product_name")
      if (deleteProductNameEl) {
        deleteProductNameEl.textContent = productName
      }

      // Set the delete link
      const confirmDeleteBtn = document.getElementById("confirm_delete")
      if (confirmDeleteBtn) {
        confirmDeleteBtn.href = `delete_product.php?id=${productId}`
      }

      // Show the modal
      if (typeof bootstrap !== "undefined") {
        const deleteModal = new bootstrap.Modal(document.getElementById("deleteModal"))
        deleteModal.show()
      }
    })
  })
}

// QR Code generation
function initializeQRCodeButtons() {
  const qrButtons = document.querySelectorAll(".qr-btn")
  qrButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault()
      const productId = this.getAttribute("data-id")
      const productName = this.getAttribute("data-name")

      // Fetch QR code from server
      fetch(`generate_qr.php?id=${productId}`)
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Display QR code in modal
            const qrModal = document.getElementById("qrModal")
            const qrImage = document.getElementById("qr_image")
            const qrProductName = document.getElementById("qr_product_name")

            if (qrImage && qrProductName) {
              qrImage.src = data.qr_code
              qrProductName.textContent = productName

              // Show modal
              if (typeof bootstrap !== "undefined") {
                const modal = new bootstrap.Modal(qrModal)
                modal.show()
              }
            }
          } else {
            showToast("Failed to generate QR code", "error")
          }
        })
        .catch((error) => {
          console.error("Error generating QR code:", error)
          showToast("Error generating QR code", "error")
        })
    })
  })
}

// Dashboard widgets
function initializeDashboardWidgets() {
  const widgetContainer = document.getElementById("widget-container")
  if (!widgetContainer) return

  // Make widgets draggable if Sortable.js is available
  if (typeof Sortable !== "undefined") {
    Sortable.create(widgetContainer, {
      animation: 150,
      handle: ".widget-header",
      onEnd: () => {
        // Save widget order to user preferences
        saveWidgetOrder()
      },
    })
  }

  // Widget settings buttons
  const widgetSettingsButtons = document.querySelectorAll(".widget-settings")
  widgetSettingsButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const widgetId = this.closest(".widget").getAttribute("data-widget-id")
      showWidgetSettings(widgetId)
    })
  })
}

function saveWidgetOrder() {
  if (typeof userId === "undefined") return

  const widgets = document.querySelectorAll(".widget")
  const order = Array.from(widgets).map((widget) => widget.getAttribute("data-widget-id"))

  fetch("save_preferences.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `widget_order=${JSON.stringify(order)}&user_id=${userId}`,
  })
}

function showWidgetSettings(widgetId) {
  // Implementation depends on specific widget types
  console.log(`Show settings for widget ${widgetId}`)
}

// Chart initialization
function initializeCharts() {
  // Check if Chart.js is available
  if (typeof Chart === "undefined") return

  // Sales Chart
  const salesChartEl = document.getElementById("sales-chart")
  if (salesChartEl) {
    fetch("get_sales_data.php")
      .then((response) => response.json())
      .then((data) => {
        const ctx = salesChartEl.getContext("2d")
        new Chart(ctx, {
          type: "line",
          data: {
            labels: data.labels,
            datasets: [
              {
                label: "Sales",
                data: data.values,
                borderColor: "rgba(13, 110, 253, 1)",
                backgroundColor: "rgba(13, 110, 253, 0.1)",
                tension: 0.4,
                fill: true,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                grid: {
                  color: "rgba(0, 0, 0, 0.05)",
                },
              },
              x: {
                grid: {
                  display: false,
                },
              },
            },
          },
        })
      })
      .catch((error) => console.error("Error loading sales data:", error))
  }

  // Product Categories Chart
  const categoriesChartEl = document.getElementById("categories-chart")
  if (categoriesChartEl) {
    fetch("get_category_data.php")
      .then((response) => response.json())
      .then((data) => {
        const ctx = categoriesChartEl.getContext("2d")
        new Chart(ctx, {
          type: "doughnut",
          data: {
            labels: data.labels,
            datasets: [
              {
                data: data.values,
                backgroundColor: [
                  "rgba(13, 110, 253, 0.7)",
                  "rgba(25, 135, 84, 0.7)",
                  "rgba(220, 53, 69, 0.7)",
                  "rgba(255, 193, 7, 0.7)",
                  "rgba(13, 202, 240, 0.7)",
                ],
                borderWidth: 1,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "bottom",
              },
            },
          },
        })
      })
      .catch((error) => console.error("Error loading category data:", error))
  }
}

// Toast function
function showToast(message, type = "success") {
  const toastContainer = document.getElementById("toast-container")
  if (!toastContainer) return

  const toast = document.createElement("div")
  toast.classList.add("toast", `toast-${type}`)
  toast.textContent = message
  toastContainer.appendChild(toast)

  setTimeout(() => {
    toast.classList.add("fade-out")
    setTimeout(() => {
      toast.remove()
    }, 300)
  }, 3000)
}

// Scanner functionality
let Html5Qrcode // Declare Html5Qrcode
function initializeScanner() {
  const scannerContainer = document.getElementById("scanner-container")
  if (!scannerContainer || typeof Html5Qrcode === "undefined") return

  const html5QrCode = new Html5Qrcode("scanner-container")
  const scanButton = document.getElementById("start-scan")
  const stopButton = document.getElementById("stop-scan")

  if (scanButton) {
    scanButton.addEventListener("click", () => {
      html5QrCode.start(
        { facingMode: "environment" },
        {
          fps: 10,
          qrbox: 250,
        },
        onScanSuccess,
        onScanFailure,
      )

      scanButton.style.display = "none"
      if (stopButton) stopButton.style.display = "inline-block"
    })
  }

  if (stopButton) {
    stopButton.addEventListener("click", () => {
      html5QrCode.stop().then(() => {
        scanButton.style.display = "inline-block"
        stopButton.style.display = "none"
      })
    })
  }
}

function onScanSuccess(decodedText, decodedResult) {
  // Handle the scanned code
  console.log(`Code scanned: ${decodedText}`, decodedResult)

  // Look up product by code
  fetch(`lookup_product.php?code=${encodeURIComponent(decodedText)}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showToast(`Found product: ${data.product.name}`, "success")

        // If we're in cart mode, add to cart
        if (document.body.classList.contains("cart-mode")) {
          addProductToCart(data.product.id, 1)
        } else {
          // Otherwise show product details
          window.location.href = `product_details.php?id=${data.product.id}`
        }
      } else {
        showToast("Product not found", "error")
      }
    })
    .catch((error) => {
      console.error("Error looking up product:", error)
      showToast("Error looking up product", "error")
    })
}

function onScanFailure(error) {
  // Handle scan failure
  console.warn(`Code scan error: ${error}`)
}

// Add product to cart via AJAX
function addProductToCart(productId, quantity) {
  const formData = new FormData()
  formData.append("product_id", productId)
  formData.append("quantity", quantity)
  formData.append("add_to_cart", "1")

  fetch("cart.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showToast(data.message, "success")
        updateCartCount(data.cart_count)
      } else {
        showToast(data.message || "Error adding to cart", "error")
      }
    })
    .catch((error) => {
      console.error("Error adding to cart:", error)
      showToast("Error adding to cart", "error")
    })
}

function updateCartCount(count) {
  const cartCountElements = document.querySelectorAll(".cart-count")
  cartCountElements.forEach((element) => {
    element.textContent = count
  })
}

// Loyalty program functions
function calculateLoyaltyPoints(amount) {
  // Basic calculation: 1 point per dollar spent
  return Math.floor(amount)
}

function getLoyaltyLevel(points) {
  if (points >= 1000) {
    return "gold"
  } else if (points >= 500) {
    return "silver"
  } else if (points >= 100) {
    return "bronze"
  } else {
    return "standard"
  }
}

function getLoyaltyDiscount(level) {
  switch (level) {
    case "gold":
      return 0.1 // 10% discount
    case "silver":
      return 0.05 // 5% discount
    case "bronze":
      return 0.02 // 2% discount
    default:
      return 0
  }
}

// Weather-based recommendations
function getWeatherRecommendations() {
  // Get user's location
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((position) => {
      const lat = position.coords.latitude
      const lon = position.coords.longitude

      // Fetch weather data
      fetch(`get_weather.php?lat=${lat}&lon=${lon}`)
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            updateWeatherRecommendations(data.weather)
          }
        })
        .catch((error) => console.error("Error fetching weather:", error))
    })
  }
}

function updateWeatherRecommendations(weather) {
  const recommendationsContainer = document.getElementById("weather-recommendations")
  if (!recommendationsContainer) return

  const recommendations = []

  // Simple logic for recommendations based on weather
  if (weather.temp > 30) {
    // Hot
    recommendations.push("cold drinks", "ice cream", "fans")
  } else if (weather.temp < 10) {
    // Cold
    recommendations.push("hot drinks", "heaters", "blankets")
  }

  if (weather.condition.includes("rain")) {
    recommendations.push("umbrellas", "raincoats")
  } else if (weather.condition.includes("snow")) {
    recommendations.push("snow shovels", "winter boots")
  }

  // Display recommendations
  if (recommendations.length > 0) {
    fetch(`get_recommended_products.php?categories=${recommendations.join(",")}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.success && data.products.length > 0) {
          let html = '<h5>Weather-based Recommendations</h5><div class="row">'

          data.products.forEach((product) => {
            html += `
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6>${product.name}</h6>
                                        <p class="text-muted">$${product.price}</p>
                                        <button class="btn btn-sm btn-success add-to-cart" 
                                                data-id="${product.id}" 
                                                data-name="${product.name}" 
                                                data-price="${product.price}">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `
          })

          html += "</div>"
          recommendationsContainer.innerHTML = html

          // Reinitialize add to cart buttons
          initializeAddToCart()
        }
      })
      .catch((error) => console.error("Error fetching recommended products:", error))
  }
}

// Initialize Bootstrap Modal (if not already initialized)
let bootstrap
if (typeof bootstrap === "undefined") {
  function bootstrapModal(element) {
    this.element = element
    this.show = () => {
      element.style.display = "block"
      element.classList.add("show")
      document.body.classList.add("modal-open")
      document.body.style.overflow = "hidden"
    }
    this.hide = () => {
      element.style.display = "none"
      element.classList.remove("show")
      document.body.classList.remove("modal-open")
      document.body.style.overflow = ""
    }
  }
  bootstrap = {
    Modal: (element) => new bootstrapModal(element),
  }
}

// Declare userId and Sortable if they are not already defined
let userId
let Sortable
