new DataTable("#basic-datatable", {
  language: {
    paginate: {
      first: '<i class="ri-arrow-left-double-line align-middle"></i>',
      previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
      next: '<i class="ri-arrow-right-s-line align-middle"></i>',
      last: '<i class="ri-arrow-right-double-line align-middle"></i>'
    }
  }
});
new DataTable("#reviewTab-datatable", {
  language: {
    paginate: {
      first: '<i class="ri-arrow-left-double-line align-middle"></i>',
      previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
      next: '<i class="ri-arrow-right-s-line align-middle"></i>',
      last: '<i class="ri-arrow-right-double-line align-middle"></i>'
    }
  }
});
new DataTable("#smsHistoryTab-datatable", {
  language: {
    paginate: {
      first: '<i class="ri-arrow-left-double-line align-middle"></i>',
      previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
      next: '<i class="ri-arrow-right-s-line align-middle"></i>',
      last: '<i class="ri-arrow-right-double-line align-middle"></i>'
    }
  }
});
new DataTable("#textpayHistoryTab-datatable", {
  language: {
    paginate: {
      first: '<i class="ri-arrow-left-double-line align-middle"></i>',
      previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
      next: '<i class="ri-arrow-right-s-line align-middle"></i>',
      last: '<i class="ri-arrow-right-double-line align-middle"></i>'
    }
  }
});
new DataTable("#allContacts-datatable", {
  language: {
    paginate: {
      first: '<i class="ri-arrow-left-double-line align-middle"></i>',
      previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
      next: '<i class="ri-arrow-right-s-line align-middle"></i>',
      last: '<i class="ri-arrow-right-double-line align-middle"></i>'
    }
  }
});
new DataTable("#contactGroup-datatable", {
  language: {
    paginate: {
      first: '<i class="ri-arrow-left-double-line align-middle"></i>',
      previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
      next: '<i class="ri-arrow-right-s-line align-middle"></i>',
      last: '<i class="ri-arrow-right-double-line align-middle"></i>'
    }
  }
});
new DataTable("#notification-datatable0", {
    "order": [], // No initial sorting - respect backend order
    "ordering": false, // Disable all sorting
    "paging": true,
    "searching": true,
    "info": true,
    language: {
        paginate: {
            first: '<i class="ri-arrow-left-double-line align-middle"></i>',
            previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
            next: '<i class="ri-arrow-right-s-line align-middle"></i>',
            last: '<i class="ri-arrow-right-double-line align-middle"></i>'
        }
    }
});


new DataTable("#sms-datatable", {
    "order": [], // No initial sorting - respect backend order
    "ordering": true, // Disable all sorting
    "paging": true,
    "searching": true,
    "info": true,
    language: {
        paginate: {
            first: '<i class="ri-arrow-left-double-line align-middle"></i>',
            previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
            next: '<i class="ri-arrow-right-s-line align-middle"></i>',
            last: '<i class="ri-arrow-right-double-line align-middle"></i>'
        }
    }
});

document.addEventListener("DOMContentLoaded", () => {
  const e = document.querySelector("#datatable-buttons");
  if (e) {
    new DataTable(e, {
      dom: "<'d-md-flex justify-content-between align-items-center my-2'Bf>rt<'d-md-flex justify-content-between align-items-center mt-2'ip>",
      responsive: true,
      buttons: [
        { extend: "copy", className: "btn btn-sm btn-secondary" },
        { extend: "csv", className: "btn btn-sm btn-secondary active" },
        { extend: "excel", className: "btn btn-sm btn-secondary" },
        { extend: "print", className: "btn btn-sm btn-secondary active" },
        { extend: "pdf", className: "btn btn-sm btn-secondary" }
      ],
      language: {
        paginate: {
          first: '<i class="ri-arrow-left-double-line align-middle"></i>',
          previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
          next: '<i class="ri-arrow-right-s-line align-middle"></i>',
          last: '<i class="ri-arrow-right-double-line align-middle"></i>'
        }
      }
    });
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const e = document.querySelector("#selection-datatable");
  if (e) {
    new DataTable(e, {
      pageLength: 7,
      lengthMenu: [7, 10, 25, 50, -1],
      select: "multi",
      language: {
        paginate: {
          first: '<i class="ri-arrow-left-double-line align-middle"></i>',
          previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
          next: '<i class="ri-arrow-right-s-line align-middle"></i>',
          last: '<i class="ri-arrow-right-double-line align-middle"></i>'
        },
        lengthMenu: "_MENU_ Items per page",
        info: 'Showing <span class="fw-semibold">_START_</span> to <span class="fw-semibold">_END_</span> of <span class="fw-semibold">_TOTAL_</span> Items'
      }
    });
  }
});

document.addEventListener("DOMContentLoaded", function () {
  const e = document.getElementById("scroll-datatable");
  if (e) {
    new DataTable(e, {
      paging: false,
      scrollCollapse: true,
      scrollY: "320px"
    });
    new DataTable("#horizontal-scroll", {
      scrollX: true,
      language: {
        paginate: {
          first: '<i class="ri-arrow-left-double-line align-middle"></i>',
          previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
          next: '<i class="ri-arrow-right-s-line align-middle"></i>',
          last: '<i class="ri-arrow-right-double-line align-middle"></i>'
        },
        lengthMenu: "_MENU_ Items per page",
        info: 'Showing <span class="fw-semibold">_START_</span> to <span class="fw-semibold">_END_</span> of <span class="fw-semibold">_TOTAL_</span> Items'
      }
    });
  }
});

new DataTable("#complex-header-datatable", {
  language: {
    paginate: {
      first: '<i class="ri-arrow-left-double-line align-middle"></i>',
      previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
      next: '<i class="ri-arrow-right-s-line align-middle"></i>',
      last: '<i class="ri-arrow-right-double-line align-middle"></i>'
    }
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const e = document.getElementById("fixed-header-datatable");
  if (e) {
    new DataTable(e, {
      fixedHeader: {
        header: true,
        headerOffset: 70
      },
      pageLength: 15,
      language: {
        paginate: {
          first: '<i class="ri-arrow-left-double-line align-middle"></i>',
          previous: '<i class="ri-arrow-left-s-line align-middle"></i>',
          next: '<i class="ri-arrow-right-s-line align-middle"></i>',
          last: '<i class="ri-arrow-right-double-line align-middle"></i>'
        }
      }
    });
  }
});
