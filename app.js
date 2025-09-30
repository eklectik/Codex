const domainTable = document.getElementById("domain-table");
const watchCountEl = document.getElementById("watch-count");
const criticalCountEl = document.getElementById("critical-count");
const caughtCountEl = document.getElementById("caught-count");
const searchInput = document.getElementById("search-input");
const tldFilter = document.getElementById("tld-filter");
const priorityFilter = document.getElementById("priority-filter");
const emptyState = document.getElementById("empty-state");

const domains = [
  {
    name: "superapp",
    tld: ".fr",
    priority: "high",
    expiry: "2023-10-04T10:30:00Z",
    dropWindow: "2023-10-05T10:45:00Z",
    status: "pending",
  },
  {
    name: "fastdelivery",
    tld: ".com",
    priority: "medium",
    expiry: "2023-10-03T07:00:00Z",
    dropWindow: "2023-10-04T07:15:00Z",
    status: "pending",
  },
  {
    name: "techwave",
    tld: ".io",
    priority: "high",
    expiry: "2023-10-02T20:00:00Z",
    dropWindow: "2023-10-03T20:05:00Z",
    status: "caught",
  },
  {
    name: "greenenergy",
    tld: ".fr",
    priority: "low",
    expiry: "2023-10-01T13:30:00Z",
    dropWindow: "2023-10-02T13:45:00Z",
    status: "expired",
  },
  {
    name: "cryptobuzz",
    tld: ".xyz",
    priority: "medium",
    expiry: "2023-10-04T23:55:00Z",
    dropWindow: "2023-10-05T00:10:00Z",
    status: "pending",
  },
  {
    name: "smartcontract",
    tld: ".eth",
    priority: "high",
    expiry: "2023-10-04T18:00:00Z",
    dropWindow: "2023-10-04T18:30:00Z",
    status: "pending",
  },
  {
    name: "boutiqueparis",
    tld: ".shop",
    priority: "low",
    expiry: "2023-10-06T09:00:00Z",
    dropWindow: "2023-10-06T09:10:00Z",
    status: "pending",
  },
  {
    name: "metaversehub",
    tld: ".net",
    priority: "medium",
    expiry: "2023-10-02T11:20:00Z",
    dropWindow: "2023-10-03T11:20:00Z",
    status: "expired",
  },
];

const formatDate = (isoString) => {
  const date = new Date(isoString);
  return date.toLocaleString("fr-FR", {
    dateStyle: "medium",
    timeStyle: "short",
  });
};

const diffFromNow = (isoString) => {
  const now = Date.now();
  const target = new Date(isoString).getTime();
  const diff = target - now;
  const absDiff = Math.abs(diff);
  const minutes = Math.floor(absDiff / 60000);
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;

  const formatted = `${hours}h ${remainingMinutes.toString().padStart(2, "0")}m`;
  return {
    formatted,
    isPast: diff < 0,
    isCritical: diff > 0 && diff <= 60 * 60 * 1000,
  };
};

const renderFilters = () => {
  const uniqueTlds = [
    "all",
    ...new Set(domains.map((domain) => domain.tld).sort()),
  ];
  uniqueTlds.forEach((tld) => {
    if (tld === "all") return;
    const option = document.createElement("option");
    option.value = tld;
    option.textContent = tld;
    tldFilter.appendChild(option);
  });
};

const renderTable = () => {
  const filters = {
    search: searchInput.value.trim().toLowerCase(),
    tld: tldFilter.value,
    priority: priorityFilter.value,
  };

  domainTable.innerHTML = "";

  const filteredDomains = domains.filter((domain) => {
    const matchesSearch = `${domain.name}${domain.tld}`
      .toLowerCase()
      .includes(filters.search);
    const matchesTld =
      filters.tld === "all" || domain.tld.toLowerCase() === filters.tld;
    const matchesPriority =
      filters.priority === "all" || domain.priority === filters.priority;
    return matchesSearch && matchesTld && matchesPriority;
  });

  emptyState.hidden = filteredDomains.length !== 0;

  filteredDomains
    .sort((a, b) => new Date(a.dropWindow) - new Date(b.dropWindow))
    .forEach((domain) => {
      const row = document.createElement("tr");

      const domainCell = document.createElement("td");
      domainCell.textContent = `${domain.name}${domain.tld}`;

      const tldCell = document.createElement("td");
      tldCell.textContent = domain.tld;

      const priorityCell = document.createElement("td");
      const badge = document.createElement("span");
      badge.className = `priority ${domain.priority}`;
      const labels = {
        high: "Haute",
        medium: "Moyenne",
        low: "Basse",
      };
      badge.textContent = labels[domain.priority];
      priorityCell.appendChild(badge);

      const expiryCell = document.createElement("td");
      expiryCell.textContent = formatDate(domain.expiry);

      const dropCell = document.createElement("td");
      const diff = diffFromNow(domain.dropWindow);
      dropCell.textContent = `${formatDate(domain.dropWindow)} (${diff.formatted})`;

      const statusCell = document.createElement("td");
      statusCell.className = `status ${domain.status}`;
      const statusLabel = {
        pending: diff.isPast ? "En retard" : "En attente",
        caught: "Catch réussi",
        expired: "Expiré",
      };
      statusCell.textContent = statusLabel[domain.status] ?? domain.status;

      if (domain.status === "pending" && diff.isPast) {
        statusCell.classList.replace("pending", "expired");
      }

      row.append(
        domainCell,
        tldCell,
        priorityCell,
        expiryCell,
        dropCell,
        statusCell
      );
      domainTable.appendChild(row);
    });
};

const updateMetrics = () => {
  const now = Date.now();
  const watch = domains.filter((domain) => domain.status === "pending");
  const critical = watch.filter((domain) => {
    const dropTime = new Date(domain.dropWindow).getTime();
    const diff = dropTime - now;
    return diff > 0 && diff <= 60 * 60 * 1000;
  });
  const caught = domains.filter((domain) => domain.status === "caught");

  watchCountEl.textContent = watch.length;
  criticalCountEl.textContent = critical.length;
  caughtCountEl.textContent = caught.length;
};

const refresh = () => {
  renderTable();
  updateMetrics();
};

renderFilters();
refresh();

searchInput.addEventListener("input", refresh);
[tldFilter, priorityFilter].forEach((element) =>
  element.addEventListener("change", refresh)
);

setInterval(refresh, 30_000);
