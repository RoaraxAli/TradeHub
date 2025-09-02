// Main JavaScript file for TradeHub

// DOM Content Loaded
document.addEventListener("DOMContentLoaded", () => {
  // Initialize tooltips
  initTooltips()

  // Initialize modals
  initModals()

  // Initialize form validation
  initFormValidation()

  // Initialize image uploads
  initImageUploads()

  // Initialize search functionality
  initSearch()
})

// Tooltip initialization
function initTooltips() {
  const tooltips = document.querySelectorAll("[data-tooltip]")
  tooltips.forEach((element) => {
    element.addEventListener("mouseenter", showTooltip)
    element.addEventListener("mouseleave", hideTooltip)
  })
}

function showTooltip(event) {
  const text = event.target.getAttribute("data-tooltip")
  const tooltip = document.createElement("div")
  tooltip.className = "tooltip"
  tooltip.textContent = text
  document.body.appendChild(tooltip)

  const rect = event.target.getBoundingClientRect()
  tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + "px"
  tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + "px"
}

function hideTooltip() {
  const tooltip = document.querySelector(".tooltip")
  if (tooltip) {
    tooltip.remove()
  }
}

// Modal functionality
function initModals() {
  const modalTriggers = document.querySelectorAll("[data-modal]")
  modalTriggers.forEach((trigger) => {
    trigger.addEventListener("click", function (e) {
      e.preventDefault()
      const modalId = this.getAttribute("data-modal")
      openModal(modalId)
    })
  })

  // Close modal on backdrop click
  document.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal-backdrop")) {
      closeModal()
    }
  })

  // Close modal on escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeModal()
    }
  })
}

function openModal(modalId) {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.classList.remove("hidden")
    document.body.style.overflow = "hidden"
  }
}

function closeModal() {
  const modals = document.querySelectorAll(".modal")
  modals.forEach((modal) => {
    modal.classList.add("hidden")
  })
  document.body.style.overflow = ""
}

// Form validation
function initFormValidation() {
  const forms = document.querySelectorAll("form[data-validate]")
  forms.forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!validateForm(this)) {
        e.preventDefault()
      }
    })
  })
}

function validateForm(form) {
  let isValid = true
  const inputs = form.querySelectorAll("input[required], textarea[required], select[required]")

  inputs.forEach((input) => {
    if (!input.value.trim()) {
      showFieldError(input, "This field is required")
      isValid = false
    } else {
      clearFieldError(input)
    }

    // Email validation
    if (input.type === "email" && input.value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(input.value)) {
        showFieldError(input, "Please enter a valid email address")
        isValid = false
      }
    }

    // Password validation
    if (input.type === "password" && input.value && input.value.length < 6) {
      showFieldError(input, "Password must be at least 6 characters long")
      isValid = false
    }
  })

  return isValid
}

function showFieldError(input, message) {
  clearFieldError(input)

  const errorDiv = document.createElement("div")
  errorDiv.className = "field-error text-red-600 text-sm mt-1"
  errorDiv.textContent = message

  input.parentNode.appendChild(errorDiv)
  input.classList.add("border-red-500")
}

function clearFieldError(input) {
  const existingError = input.parentNode.querySelector(".field-error")
  if (existingError) {
    existingError.remove()
  }
  input.classList.remove("border-red-500")
}

// Image upload functionality
function initImageUploads() {
  const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]')
  imageInputs.forEach((input) => {
    input.addEventListener("change", (e) => {
      handleImageUpload(e.target)
    })
  })
}

function handleImageUpload(input) {
  const file = input.files[0]
  if (!file) return

  // Validate file type
  if (!file.type.startsWith("image/")) {
    alert("Please select an image file")
    input.value = ""
    return
  }

  // Validate file size (10MB max)
  if (file.size > 10 * 1024 * 1024) {
    alert("Image size must be less than 10MB")
    input.value = ""
    return
  }

  // Show preview
  const reader = new FileReader()
  reader.onload = (e) => {
    showImagePreview(input, e.target.result)
  }
  reader.readAsDataURL(file)
}

function showImagePreview(input, src) {
  const previewId = input.getAttribute("data-preview")
  if (previewId) {
    const preview = document.getElementById(previewId)
    if (preview) {
      preview.src = src
      preview.classList.remove("hidden")
    }
  }
}

// Search functionality
function initSearch() {
  const searchInputs = document.querySelectorAll("input[data-search]")
  searchInputs.forEach((input) => {
    let timeout
    input.addEventListener("input", function () {
      clearTimeout(timeout)
      timeout = setTimeout(() => {
        performSearch(this.value, this.getAttribute("data-search"))
      }, 300)
    })
  })
}

function performSearch(query, type) {
  if (query.length < 2) return

  // Show loading state
  showSearchLoading(true)

  // Perform search (this would typically be an AJAX call)
  fetch(`/api/search.php?q=${encodeURIComponent(query)}&type=${type}`)
    .then((response) => response.json())
    .then((data) => {
      displaySearchResults(data)
    })
    .catch((error) => {
      console.error("Search error:", error)
    })
    .finally(() => {
      showSearchLoading(false)
    })
}

function showSearchLoading(show) {
  const loader = document.querySelector(".search-loader")
  if (loader) {
    loader.classList.toggle("hidden", !show)
  }
}

function displaySearchResults(results) {
  const container = document.querySelector(".search-results")
  if (!container) return

  if (results.length === 0) {
    container.innerHTML = '<p class="text-slate-600 text-center py-4">No results found</p>'
    return
  }

  container.innerHTML = results
    .map(
      (result) => `
        <div class="search-result-item p-3 hover:bg-slate-50 cursor-pointer">
            <h4 class="font-medium text-slate-900">${result.title}</h4>
            <p class="text-sm text-slate-600">${result.description}</p>
        </div>
    `,
    )
    .join("")
}

// Utility functions
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

function throttle(func, limit) {
  let inThrottle
  return function () {
    const args = arguments
    
    if (!inThrottle) {
      func.apply(this, args)
      inThrottle = true
      setTimeout(() => (inThrottle = false), limit)
    }
  }
}

// Format currency
function formatCurrency(amount) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount)
}

// Format date
function formatDate(date) {
  return new Intl.DateTimeFormat("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  }).format(new Date(date))
}

// Show notification
function showNotification(message, type = "info") {
  const notification = document.createElement("div")
  notification.className = `notification fixed top-4 right-4 p-4 rounded-2xl shadow-lg z-50 ${getNotificationClass(type)}`
  notification.textContent = message

  document.body.appendChild(notification)

  // Animate in
  setTimeout(() => {
    notification.classList.add("opacity-100", "translate-y-0")
  }, 10)

  // Remove after 5 seconds
  setTimeout(() => {
    notification.classList.add("opacity-0", "translate-y-2")
    setTimeout(() => notification.remove(), 300)
  }, 5000)
}

function getNotificationClass(type) {
  switch (type) {
    case "success":
      return "bg-green-100 text-green-800 border border-green-200"
    case "error":
      return "bg-red-100 text-red-800 border border-red-200"
    case "warning":
      return "bg-yellow-100 text-yellow-800 border border-yellow-200"
    default:
      return "bg-blue-100 text-blue-800 border border-blue-200"
  }
}

// Copy to clipboard
function copyToClipboard(text) {
  navigator.clipboard
    .writeText(text)
    .then(() => {
      showNotification("Copied to clipboard!", "success")
    })
    .catch(() => {
      showNotification("Failed to copy to clipboard", "error")
    })
}

// Smooth scroll to element
function scrollToElement(elementId) {
  const element = document.getElementById(elementId)
  if (element) {
    element.scrollIntoView({
      behavior: "smooth",
      block: "start",
    })
  }
}

// Export functions for global use
window.TradeHub = {
  openModal,
  closeModal,
  showNotification,
  copyToClipboard,
  scrollToElement,
  formatCurrency,
  formatDate,
}
