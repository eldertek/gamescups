<div class="carrousel">
  <?php
  if (isset($connect)) {
    foreach ($connect->query("SELECT * FROM sliders") as $row) {
      echo '
        <div class="slideshow slidefade">
          <img src="'.$row["slider"].'">
        </div>';
    }
  }
  ?>
</div>

<script type="text/javascript">
  let slideshowIndex = 1;
  showSlides(slideshowIndex);

  function showSlides(n) {
    let i;
    let slides = document.getElementsByClassName("slideshow");
    if (n > slides.length) {slideshowIndex = 1}
    for (i = 0; i < slides.length; i++) {
      slides[i].style.display = "none";
    }
    slides[slideshowIndex-1].style.display = "block";
  }

  const interval = setInterval(() => {
    showSlides(slideshowIndex += 1)
  }, 4000);
</script>