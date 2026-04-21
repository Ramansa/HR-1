$(function () {
  const current = new URLSearchParams(window.location.search).get('page') || 'dashboard';
  $('aside .nav-link').each(function () {
    const href = $(this).attr('href');
    if (href && href.includes('page=' + current)) {
      $(this).addClass('active bg-primary-subtle text-primary-emphasis');
    }
  });
});
