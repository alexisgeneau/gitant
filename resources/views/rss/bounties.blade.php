<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>Gitant — Open Bounties</title>
        <link>{{ url('/bounties') }}</link>
        <description>Latest open bounties on Gitant</description>
        <language>en</language>
        <atom:link href="{{ route('bounties.rss') }}" rel="self" type="application/rss+xml" />
        @foreach ($bounties as $bounty)
        <item>
            <title><![CDATA[{{ $bounty->issue_title }} — ${{ number_format($bounty->total_amount_cents / 100, 2) }}]]></title>
            <link>{{ route('bounties.show', $bounty) }}</link>
            <guid isPermaLink="true">{{ route('bounties.show', $bounty) }}</guid>
            <description><![CDATA[
                <strong>{{ $bounty->platformLabel() }}</strong>:
                {{ $bounty->issue_repo_owner }}/{{ $bounty->issue_repo_name }}#{{ $bounty->issue_number }}<br/>
                {{ Str::limit($bounty->issue_description, 300) }}
            ]]></description>
            <pubDate>{{ $bounty->created_at->toRfc2822String() }}</pubDate>
        </item>
        @endforeach
    </channel>
</rss>
