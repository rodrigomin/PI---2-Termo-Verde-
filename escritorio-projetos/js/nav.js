

export default function switchNav() {
    const nav = document.getElementById('nav');
    const switchNav = document.getElementById('switch-nav');

    switchNav.addEventListener('click', () => {
        nav.classList.toggle('closed');
        const hyperlinks = document.querySelectorAll('.optn');

        if (hyperlinks) {
            hyperlinks.forEach(hyperlink => {
                if (nav.classList.contains('closed')) {
                    hyperlink.href = hyperlink.href.includes('?') ? hyperlink.href + '&fechado=1' : hyperlink.href + '?fechado=1';
                    console.log(hyperlink.href);
                } else {
                    hyperlink.href = hyperlink.href.replace('&fechado=1', '').replace('?fechado=1', '');
                    console.log(hyperlink.href);
                }
            })
        }
    })
}


