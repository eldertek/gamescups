<div class="row justify-content-evenly bg-dark text-white mt-3">
<!-- NOUS CONTACTER -->
<div class="col-4">
  <h1 class="text-center mt-3">Nous contacter</h1>
  <form action="admin/messages.php" method="post">
  <div class="mb-3">
    <label for="email" class="mt-2 form-label">Addresse mail :</label>
    <input type="email" class="form-control" id="email" name="email" placeholder="john.doe@example.com">
  </div>
  <div class="mb-3">
    <label for="message" class="mt-0 form-label">Message :</label>
    <textarea class="form-control" id="message" rows="3" name="message"></textarea>
  </div>
  <div class="mb-3">
    <button class="form-control" type="submit">Prendre contact !</button>
  </div>
  </form>
</div>
<!-- NOS PARTENAIRES -->
<div class="col-4">
  <h1 class="text-center mt-3">Nos partenaires</h1>
  <div class="row row-cols-1 row-cols-md-2 g-4 mt-3">
    <?php
      if (isset($connect)) {
        foreach ($connect->query("SELECT * FROM partenaires") as $row) {
          echo '
            <div class="col">
              <div class="card">
                <a target="_blank" href="' . $row["website"] . '"><img src="' . $row["image"] . '" class="card-img-top" alt="..."></a>
              </div>
            </div>';
        }
      }
    ?>
  </div>
</div>
</div>