
document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.querySelector('.wp-block-create-block-recent-posts-showcase');
    if (!wrapper) return;

    const loadMoreWrapper = document.querySelector('.rps-load-more-wrapper');
    if (!loadMoreWrapper) return;

    const loadMoreBtn = loadMoreWrapper.querySelector('.rps-load-more-button');
    const container = wrapper.querySelector('.recent-posts-showcase');
    if (!loadMoreBtn || !container) return;
    const restUrl = recentPostsShowcaseLoadMore.restUrl;

    loadMoreBtn.addEventListener('click', async () => {
        let page = parseInt(loadMoreWrapper.dataset.currentPage) + 1;
        const postType = loadMoreWrapper.dataset.postType;
        const postsPerPage = loadMoreWrapper.dataset.postsPerPage;
        // args from dataset or settings.
        const displayImage = loadMoreWrapper.dataset.displayImage === "true";
        const displayExcerpt = loadMoreWrapper.dataset.displayExcerpt === "true";
        const displayAuthor = loadMoreWrapper.dataset.displayAuthor === "true";
        const displayDate = loadMoreWrapper.dataset.displayDate === "true";
        const layout = loadMoreWrapper.dataset.layout || "grid";

        // const taxonomy = loadMoreWrapper.dataset.taxonomy;
        // const terms = loadMoreWrapper.dataset.terms
        //     ? loadMoreWrapper.dataset.terms.split(',').filter(Boolean)
        //     : [];

        // if (taxonomy && terms.length) {
        //     params.append('taxonomy', taxonomy);
        //     terms.forEach(term => params.append('terms[]', term));
        // }

        loadMoreBtn.textContent = 'Loading...';

        // const params = new URLSearchParams({
        //     post_type: postType,
        //     page: page,
        //     posts_per_page: postsPerPage,
        //     displayImage: displayImage,
        //     taxonomy: loadMoreWrapper.dataset.taxonomy || '',
        //     terms: loadMoreWrapper.dataset.terms ? loadMoreWrapper.dataset.terms.split(',') : [],
        //     displayExcerpt: displayExcerpt,
        //     displayAuthor: displayAuthor,
        //     displayDate: displayDate,
        //     layout: layout,
        // });




        const params = new URLSearchParams();

        // required params
        params.append('post_type', postType);
        params.append('page', page);
        params.append('posts_per_page', postsPerPage);

        // display flags
        params.append('displayImage', displayImage);
        params.append('displayExcerpt', displayExcerpt);
        params.append('displayAuthor', displayAuthor);
        params.append('displayDate', displayDate);
        params.append('layout', layout);

        // taxonomy (ONLY if valid)
        const taxonomy = loadMoreWrapper.dataset.taxonomy;
        const terms = loadMoreWrapper.dataset.terms
            ? loadMoreWrapper.dataset.terms.split(',').filter(Boolean)
            : [];

        if (taxonomy && terms.length) {
            params.append('taxonomy', taxonomy);
            terms.forEach(term => params.append('terms[]', term));
        }




        const res = await fetch(`${restUrl}?${params.toString()}`);
        const data = await res.json();

        if (data.html) {
            container.insertAdjacentHTML('beforeend', data.html);
            loadMoreWrapper.dataset.currentPage = page;
            loadMoreBtn.textContent = 'Load More';
        }

        if (!data.hasMore) {
            loadMoreBtn.remove();
        }
    });
});
