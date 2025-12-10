// Filter Projects
document.addEventListener("DOMContentLoaded", () => {
  const filterBtns = document.querySelectorAll(".filter-btn")
  const projectCards = document.querySelectorAll(".project-card")

  filterBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const filterValue = this.getAttribute("data-filter")

      // Update active button
      filterBtns.forEach((b) => b.classList.remove("active"))
      this.classList.add("active")

      // Filter projects
      projectCards.forEach((card) => {
        const category = card.getAttribute("data-category")
        if (filterValue === "all" || category === filterValue) {
          card.style.display = "block"
          setTimeout(() => {
            card.style.opacity = "1"
          }, 10)
        } else {
          card.style.display = "none"
        }
      })
    })
  })
})

// Contact Form Validation
const contactForm = document.getElementById("contactForm")
if (contactForm) {
  contactForm.addEventListener("submit", function (e) {
    e.preventDefault()

    // Get form values
    const inputs = this.querySelectorAll("input, textarea")
    let isValid = true

    inputs.forEach((input) => {
      if (input.value.trim() === "") {
        isValid = false
        input.style.borderColor = "#e74c3c"
      } else {
        input.style.borderColor = "#e8ddd3"
      }
    })

    if (isValid) {
      // Email validation
      const emailInput = this.querySelector('input[type="email"]')
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

      if (!emailRegex.test(emailInput.value)) {
        alert("Email tidak valid")
        emailInput.style.borderColor = "#e74c3c"
        return
      }

      alert("Pesan Anda telah dikirim! Kami akan segera menghubungi Anda.")
      this.reset()
    } else {
      alert("Mohon isi semua field")
    }
  })
}

// Smooth scroll untuk mobile
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    const href = this.getAttribute("href")
    if (href !== "#") {
      e.preventDefault()
      const element = document.querySelector(href)
      if (element) {
        element.scrollIntoView({ behavior: "smooth" })
      }
    }
  })
})
