import Foundation

struct DashboardStats: Decodable {
    let activeBans: Int
    let activeClients: Int
    let totalReports: Int
    let newBans30d: Int
    let openDisputes: Int
}
