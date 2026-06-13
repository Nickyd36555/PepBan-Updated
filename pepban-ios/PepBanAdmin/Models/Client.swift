import Foundation

struct Client: Decodable, Identifiable {
    let id: Int
    let ownerName: String
    let ownerEmail: String
    let siteUrl: String
    let subscriptionStatus: String
    let dateRegistered: String
    let lastActive: String?
}

struct ClientsListResponse: Decodable {
    let clients: [Client]
    let total: Int
    let page: Int
    let pages: Int
}

struct ClientDetail: Decodable {
    let id: Int
    let ownerName: String
    let ownerEmail: String
    let siteUrl: String
    let subscriptionStatus: String
    let dateRegistered: String
    let lastActive: String?
    let activatedAt: String?
    let adminNotes: String?
    let emailVerifiedAt: String?
}

struct RecentReport: Decodable {
    let dateReported: String
    let reason: String
    let email: String
    let firstName: String
    let lastName: String
}

struct ClientDetailResponse: Decodable {
    let client: ClientDetail
    let reportCount: Int
    let whitelistCount: Int
    let recentReports: [RecentReport]
}
