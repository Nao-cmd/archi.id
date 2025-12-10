document.addEventListener("DOMContentLoaded", () => {
  const userMenus = document.querySelectorAll(".user-menu")

  userMenus.forEach((menu) => {
    const dropdown = menu.querySelector(".dropdown-menu")
    let hideTimeout

    // Show dropdown and clear any pending hide timeout
    menu.addEventListener("mouseenter", () => {
      if (hideTimeout) {
        clearTimeout(hideTimeout)
      }
      if (dropdown) {
        /* Set all display properties inline to ensure vertical layout */
        dropdown.style.display = "flex"
        dropdown.style.flexDirection = "column"
        dropdown.style.opacity = "1"
        dropdown.style.visibility = "visible"
      }
    })

    // Hide dropdown with delay to allow mouse movement to menu
    menu.addEventListener("mouseleave", () => {
      if (dropdown) {
        hideTimeout = setTimeout(() => {
          dropdown.style.opacity = "0"
          dropdown.style.visibility = "hidden"
          dropdown.style.display = "none"
        }, 150) // 150ms delay to prevent flickering
      }
    })

    // Keep dropdown visible when hovering over it
    if (dropdown) {
      dropdown.addEventListener("mouseenter", () => {
        if (hideTimeout) {
          clearTimeout(hideTimeout)
        }
      })

      dropdown.addEventListener("mouseleave", () => {
        hideTimeout = setTimeout(() => {
          dropdown.style.opacity = "0"
          dropdown.style.visibility = "hidden"
          dropdown.style.display = "none"
        }, 150)
      })
    }
  })
})
