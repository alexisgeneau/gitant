import { Head } from '@inertiajs/react';

interface Props {
    title: string;
    description: string;
    url?: string;
    image?: string;
    type?: 'website' | 'article' | 'profile';
    twitterCard?: 'summary' | 'summary_large_image';
    siteName?: string;
    locale?: string;
    /** Optional Schema.org JSON-LD object. Stringified into <script type="application/ld+json">. */
    jsonLd?: Record<string, unknown>;
}

const DEFAULT_IMAGE = '/images/og-default.png';

export default function SeoMeta({
    title,
    description,
    url,
    image = DEFAULT_IMAGE,
    type = 'website',
    twitterCard = 'summary_large_image',
    siteName = 'Gitant',
    locale = 'en_US',
    jsonLd,
}: Props) {
    const resolvedUrl = url ?? (typeof window !== 'undefined' ? window.location.href : undefined);
    const absoluteImage = image.startsWith('http')
        ? image
        : (typeof window !== 'undefined' ? `${window.location.origin}${image}` : image);

    return (
        <Head title={title}>
            <meta name="description" content={description} head-key="description" />

            <meta property="og:type" content={type} head-key="og:type" />
            <meta property="og:site_name" content={siteName} head-key="og:site_name" />
            <meta property="og:title" content={title} head-key="og:title" />
            <meta property="og:description" content={description} head-key="og:description" />
            <meta property="og:image" content={absoluteImage} head-key="og:image" />
            <meta property="og:locale" content={locale} head-key="og:locale" />
            {resolvedUrl && <meta property="og:url" content={resolvedUrl} head-key="og:url" />}

            <meta name="twitter:card" content={twitterCard} head-key="twitter:card" />
            <meta name="twitter:title" content={title} head-key="twitter:title" />
            <meta name="twitter:description" content={description} head-key="twitter:description" />
            <meta name="twitter:image" content={absoluteImage} head-key="twitter:image" />

            {jsonLd && (
                <script type="application/ld+json" head-key="ld+json">
                    {JSON.stringify(jsonLd)}
                </script>
            )}
        </Head>
    );
}
