"use strict";
// Local book cover images supplied for this project. They work offline.
const books = [
  {
    id: 1,
    title: "Anne of Green Gables",
    author: "L. M. Montgomery",
    lang: "en",
    isbn: "9780553213133",
    genre: "Coming-of-age · Classic",
    year: 1908,
    desc: "A warm coming-of-age story about imagination, friendship, and belonging.",
  },
  {
    id: 2,
    title: "Little Women",
    author: "Louisa May Alcott",
    lang: "en",
    isbn: "9780147514011",
    genre: "Family · Classic",
    year: 1868,
    desc: "Four sisters grow through family life, creativity, and kindness.",
  },
  {
    id: 3,
    title: "The Secret Garden",
    author: "Frances Hodgson Burnett",
    lang: "en",
    isbn: "9780141321066",
    genre: "Classic Fiction · Nature",
    year: 1911,
    desc: "A restorative story of friendship, nature, and personal growth.",
  },
  {
    id: 4,
    title: "A Little Princess",
    author: "Frances Hodgson Burnett",
    lang: "en",
    isbn: "9780141321127",
    genre: "Classic Fiction · Family",
    year: 1905,
    desc: "A young girl faces hardship with courage and generosity.",
  },
  {
    id: 5,
    title: "Pride and Prejudice",
    author: "Jane Austen",
    lang: "en",
    isbn: "9780141439518",
    genre: "Romance · Classic",
    year: 1813,
    desc: "A witty classic about family, character, and first impressions.",
  },
  {
    id: 6,
    title: "Heidi",
    author: "Johanna Spyri",
    lang: "en",
    isbn: "9780141322568",
    genre: "Classic Fiction · Family",
    year: 1881,
    desc: "A heartfelt story of alpine life, friendship, and compassion.",
  },
  {
    id: 7,
    title: "Black Beauty",
    author: "Anna Sewell",
    lang: "en",
    isbn: "9780141321035",
    genre: "Animal Fiction · Classic",
    year: 1877,
    desc: "A moving animal story centered on empathy and humane treatment.",
  },
  {
    id: 8,
    title: "The Wind in the Willows",
    author: "Kenneth Grahame",
    lang: "en",
    isbn: "9780141321134",
    genre: "Adventure · Classic",
    year: 1908,
    desc: "A gentle countryside adventure about friendship and home.",
  },
];
const bookGrid = document.getElementById("books"),
  search = document.getElementById("search"),
  count = document.getElementById("catalog-count"),
  empty = document.getElementById("empty"),
  dialog = document.getElementById("dialog"),
  dialogContent = document.getElementById("dialog-content");
let filter = "all";
// Demo reviews live in memory for this page visit only; no server/database persistence.
const demoReviews = [];
// Separate community submissions; these do not affect collection book ratings.
const communityReviews = [];
const bookReviews = (id) => demoReviews.filter((r) => r.bookId === id);
const average = (id) => {
  const rs = bookReviews(id);
  return rs.length
    ? rs.reduce((sum, r) => sum + r.rating, 0) / rs.length
    : null;
};
const starsText = (n) => "★".repeat(n) + "☆".repeat(5 - n);
function ratingSummary(id) {
  const rs = bookReviews(id),
    avg = average(id);
  return avg === null
    ? "☆☆☆☆☆  No reviews yet"
    : `${starsText(Math.round(avg))}  ${avg.toFixed(1)} · ${rs.length} review${rs.length === 1 ? "" : "s"}`;
}

const localCovers = {
  1: "images/anne-of-green-gables.png",
  2: "images/little-women.png",
  3: "images/the-secret-garden.png",
  4: "images/a-little-princess.png",
  5: "images/pride-and-prejudice.png",
  6: "images/heidi.png",
  7: "images/black-beauty.png",
  8: "images/the-wind-in-the-willows.png",
};
const cover = (b) => localCovers[b.id];
function el(tag, cls, text) {
  const node = document.createElement(tag);
  if (cls) node.className = cls;
  if (text !== undefined) node.textContent = text;
  return node;
}
function coverNode(b) {
  const wrap = el("div", "cover-wrap");
  const book = el("div", "physical-book");
  wrap.append(book);
  const img = document.createElement("img");
  img.alt = `غلاف ${b.title}`;
  img.loading = "lazy";
  img.decoding = "async";
  book.append(img);
  img.src = cover(b);
  img.onerror = () => {
    img.remove();
    const placeholder = el("div", "cover-fallback", b.title);
    book.append(placeholder);
  };
  return wrap;
}
function render() {
  const term = search.value.trim().toLocaleLowerCase();
  const matching = books.filter(
    (b) =>
      (filter === "all" || b.lang === filter) &&
      (b.title.toLocaleLowerCase().includes(term) ||
        b.author.toLocaleLowerCase().includes(term)),
  );
  bookGrid.replaceChildren();
  matching.forEach((b) => {
    const card = el("article", "book-card");
    card.append(coverNode(b));
    card.append(el("div", "book-language", b.genre));
    card.append(
      el("h3", "book-title", b.title),
      el("div", "book-author", b.author),
    );
    const rating = el("div", "card-stars", ratingSummary(b.id));
    card.append(rating);
    const bottom = el("div", "book-card-bottom");
    bottom.append(el("span", "book-meta", "English · " + b.year));
    const btn = el("button", "details", "View details ↗");
    btn.type = "button";
    btn.addEventListener("click", () => showBook(b));
    bottom.append(btn);
    card.append(bottom);
    bookGrid.append(card);
  });
  count.textContent = `${matching.length} books to explore`;
  empty.hidden = matching.length !== 0;
}
function openDialog(content) {
  dialogContent.replaceChildren(content);
  dialog.showModal();
}
function showBook(b) {
  const root = el("div", "book-detail");
  root.append(
    el("div", "eyebrow", "LUMINA BOOK COLLECTION"),
    el("h2", "detail-heading", b.title),
  );
  const top = el("div", "detail-top");
  top.append(coverNode(b));
  const info = el("div", "detail-info");
  info.append(el("div", "detail-author", b.author));
  const score = el("div", "detail-rating", ratingSummary(b.id));
  info.append(score);
  const chips = el("div", "detail-chips");
  [b.genre, "English", String(b.year)].forEach((v) =>
    chips.append(el("span", "detail-chip", v)),
  );
  info.append(chips);
  top.append(info);
  root.append(top);
  root.append(
    el("h3", "detail-subhead", "About this book"),
    el("p", "detail-description", b.desc),
  );
  const facts = el("div", "detail-facts");
  [
    ["Author", b.author],
    ["First published", String(b.year)],
    ["Language", "English"],
    ["Genre", b.genre],
  ].forEach(([key, value]) => {
    const item = el("div", "detail-fact");
    item.append(el("small", "", key), el("strong", "", value));
    facts.append(item);
  });
  root.append(facts);
  root.append(el("h3", "detail-subhead", "Reader Reviews"));
  const reviews = bookReviews(b.id);
  if (!reviews.length) {
    root.append(
      el(
        "p",
        "detail-muted",
        "No reviews yet. Be the first to share your thoughts.",
      ),
    );
  } else {
    reviews.forEach((r) => {
      const card = el("article", "detail-review");
      card.append(
        el("div", "review-stars", starsText(r.rating)),
        el("strong", "", r.name),
        el("p", "", r.text),
      );
      root.append(card);
    });
  }
  const reviewButton = el(
    "button",
    "btn primary detail-write",
    "Write a Review ★",
  );
  reviewButton.type = "button";
  reviewButton.addEventListener("click", () => showReview(b.id));
  root.append(reviewButton);
  openDialog(root);
}
function formField(label, type, name, required = true) {
  const wrap = el("label", "", label);
  const input = document.createElement("input");
  input.type = type;
  input.name = name;
  input.required = required;
  wrap.append(input);
  return wrap;
}
function showLogin() {
  const root = el("div");
  root.append(el("h2", "", "Sign in to Lumina"));
  const form = el("form", "dialog-form");
  form.append(
    formField("Email", "email", "email"),
    formField("Password", "password", "password"),
  );
  const submit = el("button", "btn primary", "Sign in");
  submit.type = "submit";
  form.append(submit);
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    alert(
      "Sign-in requires the PHP/MySQL authentication backend. This page is a front-end preview only.",
    );
  });
  root.append(
    form,
    el(
      "p",
      "dialog-note",
      "Demo interface only — no account details are stored or sent.",
    ),
  );
  openDialog(root);
}
function showReview(selectedId) {
  const root = el("div", "review-dialog");
  root.append(
    el("div", "eyebrow", "READER COMMUNITY"),
    el("h2", "", "Write a Review"),
  );
  const form = el("form", "dialog-form");
  form.append(formField("Your name", "text", "name"));
  const bookLabel = el("label", "", "Choose a book");
  const bookSelect = document.createElement("select");
  bookSelect.name = "bookId";
  bookSelect.required = true;
  books.forEach((b) => {
    const option = document.createElement("option");
    option.value = String(b.id);
    option.textContent = b.title;
    bookSelect.append(option);
  });
  if (selectedId) bookSelect.value = String(selectedId);
  bookLabel.append(bookSelect);
  form.append(bookLabel);
  const ratingLabel = el("div", "rating-label", "Your Rating");
  const starPicker = el("div", "star-picker");
  starPicker.setAttribute("role", "group");
  starPicker.setAttribute("aria-label", "Select rating from one to five stars");
  let chosen = 0;
  const buttons = [];
  function paint() {
    buttons.forEach((button, i) => {
      button.textContent = i < chosen ? "★" : "☆";
      button.classList.toggle("selected", i < chosen);
      button.setAttribute("aria-pressed", String(i + 1 === chosen));
    });
  }
  for (let i = 1; i <= 5; i++) {
    const button = el("button", "star-button", "☆");
    button.type = "button";
    button.setAttribute("aria-label", `${i} star${i === 1 ? "" : "s"}`);
    button.addEventListener("click", () => {
      chosen = i;
      paint();
    });
    buttons.push(button);
    starPicker.append(button);
  }
  form.append(ratingLabel, starPicker);
  const label = el("label", "", "Your review");
  const textarea = document.createElement("textarea");
  textarea.name = "review";
  textarea.placeholder = "Share what you loved about this book...";
  textarea.required = true;
  textarea.maxLength = 500;
  label.append(textarea);
  form.append(label);
  const submit = el("button", "btn primary", "Submit Review");
  submit.type = "submit";
  form.append(submit);
  const error = el("p", "review-error");
  error.setAttribute("role", "alert");
  form.append(error);
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!chosen) {
      error.textContent = "Please choose a star rating first.";
      return;
    }
    const data = new FormData(form);
    const bookId = Number(data.get("bookId"));
    const name = String(data.get("name")).trim().slice(0, 80);
    const review = String(data.get("review")).trim().slice(0, 500);
    if (!name || !review || !books.some((b) => b.id === bookId)) {
      error.textContent = "Please complete the form.";
      return;
    }
    demoReviews.unshift({ bookId, name, text: review, rating: chosen });
    render();
    renderCommunity();
    dialog.close();
    showBook(books.find((b) => b.id === bookId));
  });
  root.append(form);
  openDialog(root);
}
function showCommunityReview() {
  const root = el("div", "review-dialog");
  root.append(
    el("div", "eyebrow", "READER COMMUNITY"),
    el("h2", "", "Share a Book Review"),
  );
  root.append(
    el(
      "p",
      "detail-muted",
      "Recommend any book you love — it does not need to be in our collection.",
    ),
  );
  const form = el("form", "dialog-form");
  const titleField = formField("Book title", "text", "title");
  titleField.querySelector("input").maxLength = 120;
  titleField.querySelector("input").placeholder = "Enter any book title...";
  form.append(titleField);
  const authorField = formField("Author (optional)", "text", "author", false);
  authorField.querySelector("input").maxLength = 100;
  form.append(authorField);
  const nameField = formField("Your name", "text", "name");
  nameField.querySelector("input").maxLength = 80;
  form.append(nameField);
  const ratingLabel = el("div", "rating-label", "Your rating");
  const starPicker = el("div", "star-picker");
  starPicker.setAttribute("role", "group");
  starPicker.setAttribute("aria-label", "Choose one to five stars");
  let chosen = 0;
  const buttons = [];
  function paint() {
    buttons.forEach((button, i) => {
      button.textContent = i < chosen ? "★" : "☆";
      button.classList.toggle("selected", i < chosen);
      button.setAttribute("aria-pressed", String(i + 1 === chosen));
    });
  }
  for (let i = 1; i <= 5; i++) {
    const button = el("button", "star-button", "☆");
    button.type = "button";
    button.setAttribute("aria-label", `${i} star${i === 1 ? "" : "s"}`);
    button.addEventListener("click", () => {
      chosen = i;
      paint();
    });
    buttons.push(button);
    starPicker.append(button);
  }
  form.append(ratingLabel, starPicker);
  const reviewLabel = el("label", "", "Your review");
  const textarea = document.createElement("textarea");
  textarea.name = "review";
  textarea.required = true;
  textarea.maxLength = 500;
  textarea.placeholder = "What makes this book special?";
  reviewLabel.append(textarea);
  form.append(reviewLabel);
  const submit = el("button", "btn primary", "Share Review");
  submit.type = "submit";
  form.append(submit);
  const error = el("p", "review-error");
  error.setAttribute("role", "alert");
  form.append(error);
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    if (!chosen) {
      error.textContent = "Please choose a star rating first.";
      return;
    }
    const data = new FormData(form);
    const title = String(data.get("title")).trim().slice(0, 120),
      author = String(data.get("author")).trim().slice(0, 100),
      name = String(data.get("name")).trim().slice(0, 80),
      review = String(data.get("review")).trim().slice(0, 500);
    if (!title || !name || !review) {
      error.textContent = "Please complete the required fields.";
      return;
    }
    communityReviews.unshift({
      title,
      author,
      name,
      text: review,
      rating: chosen,
    });
    renderCommunity();
    dialog.close();
    document.getElementById("reviews").scrollIntoView({ behavior: "smooth" });
  });
  root.append(form);
  openDialog(root);
}
function renderCommunity() {
  const list = document.getElementById("review-list");
  list.replaceChildren();
  if (!communityReviews.length) {
    list.append(
      el(
        "p",
        "detail-muted",
        "No community stories yet. Be the first to share a book you love.",
      ),
    );
    return;
  }
  communityReviews.forEach((r) => {
    const card = el("article", "review-card community-review-card");
    card.append(
      el("div", "review-stars", starsText(r.rating)),
      el("h3", "community-book-title", r.title),
    );
    if (r.author) card.append(el("div", "community-author", `by ${r.author}`));
    card.append(el("p", "", r.text), el("small", "", `Shared by ${r.name}`));
    list.append(card);
  });
}
search.addEventListener("input", render);
document.querySelectorAll(".filter").forEach((btn) =>
  btn.addEventListener("click", () => {
    filter = btn.dataset.filter;
    document
      .querySelectorAll(".filter")
      .forEach((x) => x.classList.toggle("active", x === btn));
    render();
  }),
);
document
  .querySelectorAll("[data-modal]")
  .forEach((btn) =>
    btn.addEventListener("click", () =>
      btn.dataset.modal === "login" ? showLogin() : showCommunityReview(),
    ),
  );
document
  .getElementById("close")
  .addEventListener("click", () => dialog.close());
dialog.addEventListener("click", (e) => {
  if (e.target === dialog) dialog.close();
});
document
  .getElementById("menu")
  .addEventListener("click", () =>
    document.getElementById("navlinks").classList.toggle("open"),
  );
document
  .querySelectorAll("nav a")
  .forEach((a) =>
    a.addEventListener("click", () =>
      document.getElementById("navlinks").classList.remove("open"),
    ),
  );
render();
renderCommunity();
