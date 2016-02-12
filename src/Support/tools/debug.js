var toggleElement = document.querySelectorAll("#toggle");
if (Object.prototype.toString.call(toggleElement) === "[object NodeList]") {
	for (var i = toggleElement.length - 1; i >= 0; i--) {
		toggleElement[i].style.cursor = "pointer";
		toggleElement[i].addEventListener("click", function(e) {
			if (this.className == "show") {
				this.innerHTML = " > ";
				this.className = "hide";
				var div = this.nextElementSibling;
				div.firstElementChild.style.display = "block";
			} else {
				this.className = "show";
				this.innerHTML = " < ";
				var div = this.nextElementSibling;
				div.firstElementChild.style.display = "none";
			}
		}, false);
	}
}